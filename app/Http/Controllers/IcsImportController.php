<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Services\IcsImportService;
use App\Services\SupabaseClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class IcsImportController extends Controller
{
    public function __construct(
        private SupabaseClient $supabase,
        private IcsImportService $icsImportService
    ) {}

    /**
     * Show ICS import form
     */
    public function index(Request $request): View
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        $children = Child::forUser($userId, $this->supabase);
        $selectedChildId = $request->get('child_id', $children->first()?->id);

        return view('calendar.import', [
            'children' => $children,
            'selectedChildId' => $selectedChildId,
            'supportedExtensions' => IcsImportService::getSupportedExtensions(),
        ]);
    }

    /**
     * Preview ICS file before importing
     */
    public function preview(Request $request): View
    {
        $validated = $request->validate([
            'ics_file' => 'required|file|max:5120', // 5MB max
            'child_id' => 'required|integer|exists:children,id',
        ]);

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        // Verify child belongs to user
        $child = Child::find($validated['child_id'], $this->supabase);
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        // Validate ICS file
        $errors = $this->icsImportService->validateIcsFile($request->file('ics_file'));
        if (! empty($errors)) {
            return back()->withErrors(['ics_file' => implode(' ', $errors)]);
        }

        try {
            $preview = $this->icsImportService->previewIcsFile($request->file('ics_file'));

            return view('calendar.import-preview', [
                'child' => $child,
                'preview' => $preview,
                'fileName' => $request->file('ics_file')->getClientOriginalName(),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['ics_file' => 'Error reading ICS file: '.$e->getMessage()]);
        }
    }

    /**
     * Import ICS file
     */
    public function import(Request $request): View
    {
        $validated = $request->validate([
            'ics_file' => 'required|file|max:5120',
            'child_id' => 'required|integer|exists:children,id',
            'confirm_import' => 'required|boolean',
        ]);

        if (! $validated['confirm_import']) {
            return back()->withErrors(['confirm_import' => 'Please confirm the import']);
        }

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        // Verify child belongs to user
        $child = Child::find($validated['child_id'], $this->supabase);
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        try {
            $result = $this->icsImportService->importIcsFile(
                $request->file('ics_file'),
                $validated['child_id'],
                Session::get('user_id')
            );

            // If HTMX request, return the calendar update
            if ($request->header('HX-Request')) {
                $timeBlocks = $child->timeBlocks($this->supabase);
                $timeBlocksByDay = [];
                foreach (range(1, 7) as $day) {
                    $timeBlocksByDay[$day] = $timeBlocks->where('day_of_week', $day);
                }

                return view('calendar.partials.grid', [
                    'timeBlocksByDay' => $timeBlocksByDay,
                    'reviewSlotsByDay' => [],
                    'selectedChild' => $child,
                ])->with('htmx_trigger', 'icsImported');
            }

            return view('calendar.import-result', [
                'child' => $child,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['import' => 'Import failed: '.$e->getMessage()]);
        }
    }

    /**
     * Import from URL
     */
    public function importUrl(Request $request): View
    {
        $validated = $request->validate([
            'ics_url' => 'required|url',
            'child_id' => 'required|integer|exists:children,id',
            'confirm_import' => 'required|boolean',
        ]);

        if (! $validated['confirm_import']) {
            return back()->withErrors(['confirm_import' => 'Please confirm the import']);
        }

        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_token');

        // Ensure SupabaseClient has the user's access token for RLS
        if ($accessToken) {
            $this->supabase->setUserToken($accessToken);
        }

        // Verify child belongs to user
        $child = Child::find($validated['child_id'], $this->supabase);
        if (! $child || $child->user_id !== $userId) {
            abort(403);
        }

        try {
            $result = $this->icsImportService->importIcsFromUrl(
                $validated['ics_url'],
                $validated['child_id'],
                Session::get('user_id')
            );

            // If HTMX request, return the calendar update
            if ($request->header('HX-Request')) {
                $timeBlocks = $child->timeBlocks($this->supabase);
                $timeBlocksByDay = [];
                foreach (range(1, 7) as $day) {
                    $timeBlocksByDay[$day] = $timeBlocks->where('day_of_week', $day);
                }

                return view('calendar.partials.grid', [
                    'timeBlocksByDay' => $timeBlocksByDay,
                    'reviewSlotsByDay' => [],
                    'selectedChild' => $child,
                ])->with('htmx_trigger', 'icsImported');
            }

            return view('calendar.import-result', [
                'child' => $child,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['import' => 'Import failed: '.$e->getMessage()]);
        }
    }

    /**
     * Show help/documentation for ICS import
     */
    public function help(): View
    {
        return view('calendar.import-help');
    }
}
