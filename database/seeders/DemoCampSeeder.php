<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Camp;
use App\Models\CampDay;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Reproduces the real "Plachta 2026 - 1. turnus" (Rio themed) schedule from the
 * reference PDF so the app has meaningful data to explore.
 */
class DemoCampSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first()
            ?? User::factory()->create(['name' => 'Vedúci tábora', 'email' => 'veduci@farnost.sk']);

        // Shared activity library ---------------------------------------------
        $library = [
            ['Ranné chvály', 'spiritual', 'sky', 30, 'Spoločný ranný modlitbový začiatok dňa.'],
            ['Scénka', 'skit', 'violet', 15, 'Krátka divadelná scénka rozvíjajúca príbeh tábora.'],
            ['Zoznamovačky', 'game', 'emerald', 180, 'Vymyslieť názov skupiny a erb, pokrik, rozdelenie do skupiniek.'],
            ['Stanoviská', 'station', 'teal', 120, 'Putovanie skupiniek po stanoviskách s úlohami.'],
            ['Vodné hry', 'game', 'cyan', 120, 'Mrazené tričko, prenášanie vody s hubkou, vodné balóny.'],
            ['Olympiáda', 'sport', 'lime', 120, 'Porovnanie síl skupiniek v hlavolamoch a pohybových aktivitách.'],
            ['Tvorenie', 'craft', 'orange', 90, 'Výroba erbov, škrabošiek a rekvizít.'],
            ['Slovko', 'spiritual', 'rose', 30, 'Krátke duchovné zamyslenie na záver programu.'],
            ['Bomba!', 'game', 'emerald', 60, 'Pohybová hra s príkazmi: bomba, snajper, potopa, horí, vtáky.'],
            ['Karneval', 'game', 'fuchsia', 120, 'Záverečný karneval so škraboškami a hrou o elektrinu.'],
            ['Obed', 'meal', 'amber', 30, 'Spoločný obed.'],
            ['Odpočinok', 'meal', 'slate', 60, 'Popoludňajší oddych po obede.'],
            ['Upratovanie', 'other', 'teal', 30, 'Upratovanie a príprava materiálu na ďalší deň.'],
        ];

        foreach ($library as [$name, $category, $color, $duration, $desc]) {
            Activity::firstOrCreate(
                ['name' => $name, 'category' => $category],
                ['color' => $color, 'default_duration' => $duration, 'description' => $desc, 'created_by' => $user->id],
            );
        }

        if (Camp::where('name', 'Plachta – 1. turnus')->exists()) {
            $this->command->info('Demo camp already exists, skipping.');

            return;
        }

        // Camp ----------------------------------------------------------------
        $camp = Camp::create([
            'owner_id' => $user->id,
            'name' => 'Plachta – 1. turnus',
            'year' => 2026,
            'description' => 'Letný denný tábor v štýle filmu Rio. Deti prichádzajú 8:00–8:30 a odchádzajú 16:00–16:30.',
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-10',
        ]);
        $camp->members()->attach($user->id, ['role' => 'owner']);

        // Time skeleton (columns) --------------------------------------------
        $slotDefs = [
            'ranne' => ['Ranné chvály', '08:00', '08:30', 'activity', 'sky'],
            'scenka_am' => ['Scénka', '08:30', '09:00', 'activity', 'violet'],
            'blok1' => ['BLOK I.', '09:00', '12:00', 'activity', 'emerald'],
            'obed' => ['Obed', '12:00', '12:30', 'fixed', 'amber'],
            'odpocinok' => ['Odpočinok', '12:30', '13:30', 'fixed', 'slate'],
            'scenka_pm' => ['Scénka', '13:30', '14:00', 'activity', 'violet'],
            'blok2' => ['BLOK II.', '14:00', '15:30', 'activity', 'emerald'],
            'slovko' => ['Slovko', '15:30', '16:00', 'activity', 'rose'],
            'upratovanie' => ['Upratovanie', '16:00', '16:30', 'activity', 'teal'],
        ];

        $position = 0;
        $slotTime = []; // key => [start, durationMinutes]
        foreach ($slotDefs as $key => [$name, $start, $end, $kind, $color]) {
            $camp->timeSlots()->create([
                'name' => $name,
                'start_time' => $start,
                'end_time' => $end,
                'kind' => $kind,
                'color' => $color,
                'position' => $position++,
            ]);
            $slotTime[$key] = [$start, (int) abs(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)))];
        }

        // Days (Mon–Fri) ------------------------------------------------------
        $days = [];
        $cursor = Carbon::parse('2026-07-06');
        $pos = 0;
        while ($cursor->lte(Carbon::parse('2026-07-10'))) {
            $days[$cursor->toDateString()] = $camp->days()->create([
                'date' => $cursor->toDateString(),
                'position' => $pos++,
                'is_trip' => in_array($cursor->dayOfWeek, [Carbon::TUESDAY, Carbon::THURSDAY], true),
            ]);
            $cursor->addDay();
        }

        // Day meta ------------------------------------------------------------
        $this->setDayMeta($days['2026-07-07'], ['name_days' => 'Patrik, Eli', 'birthdays' => 'Danielka', 'trip_name' => 'Výlet na hrad Devín', 'materials' => 'šiltovky a viac vody']);
        $this->setDayMeta($days['2026-07-08'], ['name_days' => 'Oliver', 'materials' => 'hubky, poháriky, vodné balóny']);
        $this->setDayMeta($days['2026-07-09'], ['name_days' => 'Ivan, Peto Svitek', 'birthdays' => 'Peto Schmidt', 'trip_name' => 'Cyklovýlet + hasiči', 'materials' => 'info rodičom o náhradnom oblečení, servis na bicykle']);
        $this->setDayMeta($days['2026-07-10'], ['name_days' => 'Amália, Eli', 'birthdays' => 'Mišo B.', 'materials' => 'plechovky, poháriky, kľúč, škrabošky, príprava na karneval']);

        // Program (cells) -----------------------------------------------------
        $activityByName = Activity::pluck('id', 'name');

        // date => [slotKey => [title, description, responsible, activityName]]
        $program = [
            '2026-07-06' => [
                'ranne' => ['Ranné chvály', 'Spoločný ranný začiatok.', null, 'Ranné chvály'],
                'scenka_am' => ['Blu sa zoznamuje s Lindou', 'Blu sa zrazu ocitne na scénke a Linda si ho zoberie k sebe. Táborový tanec.', 'Každý animátor', 'Scénka'],
                'blok1' => ['Zoznamovačky, rozdelenie do skupín', 'Názov skupiny a erb, pokrik, nájdi svojho animátora.', 'Sára', 'Zoznamovačky'],
                'scenka_pm' => ['Linda sa zoznamuje s vedcom', 'Prichádza šialený vedec a chce Blua dostať do Ria.', 'Sára', 'Scénka'],
                'blok2' => ['Stanoviská – olympiáda', 'Porovnanie síl skupiniek – príprava na cestu do Ria.', 'Lucka', 'Olympiáda'],
                'slovko' => ['Povolanie Petra', 'Spoznávajme Ježiša v druhých ľuďoch.', 'Lucka', 'Slovko'],
                'upratovanie' => ['Príprava na utorok', 'Zbaliť materiál na výlet, lekárnička, info pre rodičov o výlete.', null, 'Upratovanie'],
            ],
            '2026-07-07' => [
                'ranne' => ['Ranné chvály', null, null, 'Ranné chvály'],
                'scenka_am' => ['Cesta do Rio de Janeiro', 'Blue stretne Pedra a Nica, spolu utekajú do autobusu.', 'Monca', 'Scénka'],
                'blok1' => ['Bomba!', 'Pohybová hra počas cesty, v skupinkách 5 úloh za písmená mena Perla.', 'Lucka', 'Bomba!'],
                'scenka_pm' => ['Stretnutie s Perlou', 'Blue v klietke stretne Perlu, ktorá chce utiecť.', 'Mišo', 'Scénka'],
                'blok2' => ['Devínske hradné hry', 'Hry na hrade a v jeho okolí.', 'Števko', 'Stanoviská'],
                'slovko' => ['Krok do neznáma', 'Aj my musíme niekedy vykročiť z pohodlia za Ježišom.', 'Mišo', 'Slovko'],
                'upratovanie' => ['Upratovanie', null, null, 'Upratovanie'],
            ],
            '2026-07-08' => [
                'ranne' => ['Ranné chvály', null, null, 'Ranné chvály'],
                'scenka_am' => ['Blue a Perla v klietke', 'Nigel ich unesie a odovzdá pašerákom.', 'Peto', 'Scénka'],
                'blok1' => ['Putovanie po stanoviskách', 'Ranné stanoviská + bonusové (vlajky, rybárik).', 'Peto', 'Stanoviská'],
                'scenka_pm' => ['Pašeráci a Nigel', 'Pašeráci sa tešia z úlovku, Blue a Perla premýšľajú ako ujsť.', 'Peto', 'Scénka'],
                'blok2' => ['Vodný deň', 'Mrazené tričko, hadicový hadík, prenášanie vody s hubkou, vodné balóny.', 'Števko', 'Vodné hry'],
                'slovko' => ['Milosrdný samaritán', 'Každý hriešnik má budúcnosť a každý svätý minulosť.', null, 'Slovko'],
                'upratovanie' => ['Upratovanie', 'Skontrolovať počasie, lekárnička, servis na bicykle.', null, 'Upratovanie'],
            ],
            '2026-07-09' => [
                'ranne' => ['Ranné chvály', null, null, 'Ranné chvály'],
                'scenka_am' => ['Útek pred pašerákmi', 'Blue a Perla so spojenými nohami sa učia spolupracovať a letieť.', 'Marek a Mišo', 'Scénka'],
                'blok1' => ['Obchodníci, štipcovačka, hajzelbaba', 'Rozcvička (Nico a Pedro) a obchodné hry.', null, 'Stanoviská'],
                'scenka_pm' => ['Rozchod Blua a Perly', 'Perla chce do džungle, Blue hľadať Lindu – rozídu sa.', 'Mišo', 'Scénka'],
                'blok2' => ['Hasiči a prekážková dráha', 'Ukážka hasičov a prekážková dráha.', null, 'Olympiáda'],
                'slovko' => ['Slovko', null, 'Mišo', 'Slovko'],
                'upratovanie' => ['Upratovanie', 'Lepšie upratať bicykle, prilby na riadidlách.', null, 'Upratovanie'],
            ],
            '2026-07-10' => [
                'ranne' => ['Ranné chvály', null, null, 'Ranné chvály'],
                'scenka_am' => ['Nigel chytí Perlu', 'Nigel odvedie Perlu, Blue zvoláva kamošov na pomoc.', 'Hanka', 'Scénka'],
                'blok1' => ['Obchodníci, štipcovačka, formulky', 'Dopoludňajšie hry a obchodné tabule.', 'Števko', 'Stanoviská'],
                'scenka_pm' => ['Veľké oslobodenie Perly', 'Blue s kamošmi Nicom, Pedrom a tukanom oslobodia Perlu.', 'Hanka', 'Scénka'],
                'blok2' => ['Karneval + hra o elektrinu', 'Záverečný karneval so škraboškami.', 'Števko', 'Karneval'],
                'slovko' => ['Vyhodnotenie', 'Zhodnotenie týždňa a chill.', 'Hanka', 'Slovko'],
                'upratovanie' => ['Upratovanie', 'Príprava na karneval, stanoviská ping-pong loptičky.', null, 'Upratovanie'],
            ],
        ];

        foreach ($program as $date => $cells) {
            $day = $days[$date];
            foreach ($cells as $slotKey => [$title, $description, $responsible, $activityName]) {
                if (! isset($slotTime[$slotKey])) {
                    continue;
                }
                [$start, $duration] = $slotTime[$slotKey];
                $day->entries()->create([
                    'activity_id' => $activityByName[$activityName] ?? null,
                    'start_time' => $start,
                    'duration' => $duration,
                    'title' => $title,
                    'description' => $description,
                    'responsible' => $responsible,
                ]);
            }
        }

        $this->command->info('Demo camp "Plachta – 1. turnus" created.');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function setDayMeta(CampDay $day, array $meta): void
    {
        $day->update($meta);
    }
}
