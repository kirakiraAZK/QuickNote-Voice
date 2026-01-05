<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Meeting;
use Illuminate\Support\Str; // Tambahkan ini untuk helper Str

class MeetingController extends Controller
{
    public function index()
    {
        return view('pages.main');
    }

    public function history()
    {
        $meetings = Meeting::latest()->get();
        return view('pages.history', compact('meetings'));
    }

    // METHOD STORE YANG SUDAH DI-UPDATE UNTUK AUDIO CUSTOM NAME
    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'title' => 'required|string|max:255',
            'language' => 'required|string|max:50',
            'transcript' => 'required|string',
            'summary' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:webm,mp3,wav,m4a|max:51200', 
        ]);

        try {
            $audioPath = null;

            // 2. Cek & Upload File Audio dengan Penamaan Kustom
            if ($request->hasFile('audio_file')) {
                $file = $request->file('audio_file');
                
                // Format nama file: TANGGAL-JUDUL.ekstensi (YYYY-MM-DD-judul-slug.webm)
                // Contoh: 2025-12-15-daily-standup.webm
                $filename = date('Y-m-d') . '-' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
                
                // Simpan dengan nama baru di folder 'recordings'
                $path = $file->storeAs('recordings', $filename, 'public');
                $audioPath = $path;
            }

            // 3. Simpan ke Database
            $meeting = Meeting::create([
                'title' => $request->title,
                'language' => $request->language,
                'transcript' => $request->transcript,
                'summary' => $request->summary,
                'audio_path' => $audioPath, 
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Meeting & Audio berhasil disimpan!',
                'data' => $meeting
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        
        // Hapus file audio fisik jika ada
        if ($meeting->audio_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($meeting->audio_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($meeting->audio_path);
        }

        $meeting->delete();
        return redirect()->back()->with('success', 'Data dihapus.');
    }
}

