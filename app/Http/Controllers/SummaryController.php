<?php

namespace App\Http\Controllers;

use App\Models\AiSummary;
use App\Models\Camp;
use App\Models\CampDay;
use App\Services\GeminiService;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * AI summary of every leader's reviews for a single day.
     */
    public function day(Request $request, CampDay $day): JsonResponse
    {
        $this->authorize('update', $day->camp);

        $day->load(['camp', 'reviews.user:id,name', 'reviews.answers.question:id,text,scope', 'reviews.ratings.entry:id,title,activity_id', 'reviews.ratings.entry.activity:id,name']);

        if ($day->reviews->isEmpty()) {
            return response()->json(['message' => 'Tento deň zatiaľ nikto nezhodnotil.'], 422);
        }

        return $this->respond($request, $day->camp, $day, $this->dayText($day), "zhodnotenia dňa ({$day->date->format('j.n.')})");
    }

    /**
     * AI summary of every leader's reviews across the whole camp.
     */
    public function camp(Request $request, Camp $camp): JsonResponse
    {
        $this->authorize('update', $camp);

        $camp->load(['days.reviews.user:id,name', 'days.reviews.answers.question:id,text,scope', 'days.reviews.ratings.entry:id,title,activity_id', 'days.reviews.ratings.entry.activity:id,name']);

        $hasReviews = $camp->days->contains(fn (CampDay $d) => $d->reviews->isNotEmpty());
        if (! $hasReviews) {
            return response()->json(['message' => 'Tábor zatiaľ nemá žiadne zhodnotenia.'], 422);
        }

        $parts = $camp->days
            ->filter(fn (CampDay $d) => $d->reviews->isNotEmpty())
            ->map(fn (CampDay $d) => $this->dayText($d))
            ->implode("\n\n");

        return $this->respond($request, $camp, null, $parts, "celý tábor „{$camp->name}“");
    }

    /**
     * Build the prompt, call Gemini with the current user's key, store the result
     * as this scope's summary, and return it as both markdown and HTML.
     */
    private function respond(Request $request, Camp $camp, ?CampDay $day, string $reviewsText, string $scopeLabel): JsonResponse
    {
        $key = $request->user()->gemini_api_key;

        if (! $key) {
            return response()->json([
                'message' => 'Najprv pridaj Gemini API kľúč v Nastavenia → AI súhrny.',
                'needs_key' => true,
            ], 422);
        }

        $prompt = <<<PROMPT
            Si asistent, ktorý pomáha animátorom na detskom letnom tábore. Nižšie sú spätné väzby
            vedúcich – hviezdičkové hodnotenia (1–5) jednotlivých aktivít, dôvody a poznámky.
            Napíš stručný, konkrétny súhrn po slovensky pre: {$scopeLabel}.

            Zhrň:
            • čo fungovalo dobre (najlepšie hodnotené aktivity),
            • čo treba zlepšiť (najnižšie hodnotené a spomínané dôvody),
            • odpovede na vlastné otázky tábora (ak sú v dátach) zapracuj do súhrnu,
            • celkový dojem a odporúčania do budúcna.

            Používaj krátke odseky alebo odrážky. Nevymýšľaj si nič, čo nie je v dátach.

            DÁTA:
            {$reviewsText}
            PROMPT;

        try {
            $summary = $this->gemini->generate($key, $prompt);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $saved = AiSummary::updateOrCreate(
            ['camp_id' => $camp->id, 'camp_day_id' => $day?->id],
            ['user_id' => $request->user()->id, 'summary' => $summary],
        );

        return response()->json([
            'summary' => $summary,
            'summary_html' => Markdown::toHtml($summary),
            'saved_at' => $saved->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Format one day's reviews into readable text for the prompt.
     */
    private function dayText(CampDay $day): string
    {
        $lines = ["=== Deň {$day->date->format('j.n.Y')} ==="];

        foreach ($day->reviews as $review) {
            $name = $review->user->name;
            $lines[] = "- Vedúci {$name}:";

            foreach ($review->ratings as $r) {
                $title = $r->entry?->title ?: $r->entry?->activity?->name ?: 'Aktivita';
                $reason = $r->reason ? " (dôvod: {$r->reason})" : '';
                $lines[] = "    • {$title}: {$r->rating}/5{$reason}";
            }
            if ($review->notes) {
                $lines[] = "    Poznámka: {$review->notes}";
            }
            if ($review->camp_rating) {
                $reason = $review->camp_reason ? " (dôvod: {$review->camp_reason})" : '';
                $lines[] = "    Celkové hodnotenie tábora: {$review->camp_rating}/5{$reason}";
            }
            foreach ($review->answers as $answer) {
                $lines[] = "    Otázka „{$answer->question->text}“: {$answer->answer}";
            }
        }

        return implode("\n", $lines);
    }
}
