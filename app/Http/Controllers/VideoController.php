<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertVideo;
use App\Models\Event;
use App\Models\File;
use App\Models\Video;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\FFProbe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        if($request->has('event')) {
            return response(Video::where('event', $request->event)->paginate(20));
        } else {
            return response(Video::paginate(20));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \FFMpeg\FFProbe  $ffprobe  Inject FFProbe instance
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, FFProbe $ffprobe) {

        $this->validate($request, [
            'event' => 'required|exists:events,id',
            'file' => 'required|file|mimetypes:video/mp4|mimes:mp4'
        ]);

        $event = Event::where('id', $request->input('event'))->first();

        $path = $request->file('file')->store("public/video/{$event->getFolder()}");
        $video = null;

        DB::transaction(function () use ($path, &$event, &$request, &$video) {
            $file = new File();
            $file->path = $path;
            $file->save();

            dispatch(new ConvertVideo($file, $event, [
                'boomerang' => $request->query('boomerang', false),
                'framerate' => $request->query('framerate', 5),
            ]));

            $video = Video::create([
                'event' => $event->id,
                'file' => $file->id,
            ]);
        });

        return response($video, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function show(Video $video) {
        return response($video);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Video $video) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function destroy(Video $video) {
        //
    }
}
