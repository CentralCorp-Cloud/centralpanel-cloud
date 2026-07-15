<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminNewsController extends Controller
{
    public function index()
    {
        $news = News::orderByDesc('published_at')->orderByDesc('id')->get();

        return view('admin.news.index', compact('news'));
    }

    public function create()
    {
        return view('admin.news.edit', ['article' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['published_at'] ??= now();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('news_images', 'public');
        }

        News::create($data);
        $this->bumpLauncherCache();

        return redirect()->route('admin.news.index')->with('success', __('messages.flash.news_created'));
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', ['article' => $news]);
    }

    public function update(Request $request, News $news)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($news);
            $data['image'] = $request->file('image')->store('news_images', 'public');
        }

        $news->update($data);
        $this->bumpLauncherCache();

        return redirect()->route('admin.news.edit', $news)->with('success', __('messages.flash.news_updated'));
    }

    public function destroy(News $news)
    {
        $this->deleteImage($news);
        $news->delete();
        $this->bumpLauncherCache();

        return redirect()->route('admin.news.index')->with('success', __('messages.flash.news_deleted'));
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:100000',
            'author' => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);
    }

    private function deleteImage(News $news): void
    {
        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }
    }

    private function bumpLauncherCache(): void
    {
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);
    }
}
