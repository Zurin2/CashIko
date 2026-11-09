<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Assistant (§8.12): premium chat kept server-side via AssistantService. */
class AssistantController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        if ($business->plan !== 'premium') {
            return redirect()->to('/pricing')->with('error', 'Premium feature.');
        }

        $messages = $business->chatMessages()->orderBy('created_at')->get()->map(
            fn ($m) => ['role' => $m->role, 'content' => $m->content, 'id' => $m->id]
        );

        return Inertia::render('Assistant/Index', ['messages' => $messages]);
    }

    public function ask(Request $request)
    {
        $business = $request->user()->business;
        if ($business->plan !== 'premium') {
            return redirect()->to('/assistant')->with('error', 'Premium feature.');
        }

        $data = $request->validate(['question' => 'required|string|max:400']);

        $answer = app(AssistantService::class)->answer($business, $data['question']);

        $business->chatMessages()->createMany([
            ['role' => 'user', 'content' => $data['question']],
            ['role' => 'assistant', 'content' => $answer],
        ]);

        return redirect()->to('/assistant');
    }

    public function clear(Request $request)
    {
        $business = $request->user()->business;
        $business->chatMessages()->get()->each->delete();

        return redirect()->to('/assistant')->with('success', 'Cleared chat history.');
    }
}
