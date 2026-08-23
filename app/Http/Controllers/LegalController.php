<?php

namespace App\Http\Controllers;

use App\Support\LegalDocument;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * The privacy policy. Public: people whose data a camp organiser entered
     * need to be able to read it without an account.
     */
    public function privacy(): Response
    {
        return $this->document('privacy');
    }

    /**
     * The terms of use. Public, and linked from the registration form.
     */
    public function terms(): Response
    {
        return $this->document('terms');
    }

    protected function document(string $document): Response
    {
        $locale = app()->getLocale();

        return Inertia::render('legal/Document', [
            'document' => $document,
            'html' => LegalDocument::render($document, $locale),
            'updatedAt' => LegalDocument::updatedAt($document, $locale),
        ]);
    }
}
