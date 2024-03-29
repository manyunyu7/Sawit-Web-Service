<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\News;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function adminCreate()
    {
        return view('admin.news.create');
    }

    public function read($id)
    {
        $news = DB::table('news')
            ->leftJoin('users', 'users.id', '=', 'news.posted_by')
            ->select(
                'news.*',
                'users.name as user_name',
                'users.profile_url as user_photo',
            )->where('news.id', '=', "$id")->get();
        if ($news != null || $news != "") {
            $news = $news[0];
        } else {
            $news = null;
        }

        return view('news.index')
            ->with(compact('news'));
    }

    public function update(Request $request){
        $rules = [
            'title' => 'required',
            'update_content' => 'required',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        $customMessages = [
            'required' => 'Mohon Isi Kolom :attribute terlebih dahulu'
        ];

        $this->validate($request, $rules, $customMessages);

        $news = News::findOrFail($request->update_idx);
        if ($request->file('image') == "") {
            //IF IMAGE NOT REPLACED
            $news->update([
                'title' => $request->title,
                'content' => $request->update_content
            ]);
        }else{
            //IF IMAGE IS REPLACED
            $basePath = "photo/news/";
            if (\File::exists(public_path("$basePath/" . $news->pict_url))) {
                \File::delete(public_path("$basePath/" . $news->pict_url));
            }
            $image = $request->file('image');
            $destinationPath = 'photo/news/';
            $file_name = Carbon::now()->timestamp . "_" . $image->getClientOriginalName();
            $image->move($destinationPath, $file_name);
            $news->update([
                'pict_url' => $file_name,
                'title' => $request->title,
                'content' => $request->update_content
            ]);
        }

        if ($news){
            if (Helper::isAPI()){
                return response()->json([
                    'http_response' => 200,
                    'status' => 1,
                    'message_id' => 'Berhasil Mengupdate News Feed',
                    'message' => 'News Feed Update has failed',
                ]);
            }else{
                return redirect("$request->redirectTo")->with(['success' => "News Feed Berhasil Diupdate"]);
            }
        }else{
            if (Helper::isAPI()){
                return response()->json([
                    'http_response' => 400,
                    'status' => 0,
                    'message_id' => 'Gagal Mengupdate News Feed',
                    'message' => 'News Feed Update has failed',
                ]);
            }else{
                return redirect("$request->redirectTo")->with(['success' => "News Feed Gagal Diupdate"]);
            }
        }
    }

    public function destroy($id, Request $request)
    {

        $news = News::findOrFail($id);

        $delete = $news->delete();

        $isAPI = Helper::isAPI();

        $basePath = "photo/news/";
        if (\File::exists(public_path("$basePath/" . $news->pict_url))) {
            \File::delete(public_path("$basePath/" . $news->pict_url));
        }

        if ($delete) {
            if ($isAPI) {
                return response()->json([
                    'http_response' => 200,
                    'status' => 1,
                    'message_id' => 'Berhasil Menghapus di News Feed',
                    'message' => 'News Post Success',
                ]);
            } else {
                return redirect("$request->redirectTo")->with(['success' => "News Feed Berhasil Dihapus"]);
            }
        } else {
            if ($isAPI) {
                return response()->json([
                    'http_response' => 400,
                    'status' => 0,
                    'message_id' => 'Gagal Menghapus di News Feed',
                    'message' => 'News Post Failed',
                ]);
            } else {
                return redirect("$request->redirectTo")->with(['success' => "News Feed Gagal Dihapus"]);
            }
        }
    }

    public function masterGetNews()
    {
        $status=1;
        $http=200;
        $isAPI = Helper::isAPI();
        $news = DB::table('news')
            ->leftJoin('users', 'users.id', '=', 'news.posted_by')
            ->select(
                'news.*',
                'users.name as user_name',
                'users.profile_url as user_photo',
            )->orderBy('news.id','desc')->get();
        if (!$isAPI){
            return view('admin/news/manage')
                ->with(compact('news'));
        }
        if ($news == null || $news == ""){
            $status = 0;
            $http = 400;
        }
        return response()->json([
            'http_response' => $http,
            'status' => $status,
            'message_id' => 'Berhasil Mengambil Data News Feed',
            'message' => 'News Feed Retrieved Successfully',
            'data_count' => count($news),
            'data' => $news
        ]);

    }

    public function store(Request $request)
    {
        $rules = [
            'image' => 'required',
//            'image.*' => 'mimes:jpeg,png,jpg,gif,svg,png|max:12048',
            'title' => 'required',
            'contentz' => 'required',
            'user_id' => 'required|numeric',
        ];

        $customMessages = [
            'required' => 'Mohon Isi Kolom :attribute terlebih dahulu'
        ];

        $this->validate($request, $rules, $customMessages);

        $image = $request->file('image');
        $destinationPath = 'photo/news/';
        $file_name = Carbon::now()->timestamp . "_" . $image->getClientOriginalName();
        $image->move($destinationPath, $file_name);
//        Storage::disk('public')->putFileAs($destinationPath, $image, $file_name);
        $store = News::create([
            'title' => $request->title,
            'pict_url' => $file_name,
            'content' => $request->contentz,
            'link' => $request->link,
            'posted_by' => $request->user_id,
        ]);


        $redirectTo = "admin/news/index";
        if ($store) {
            if (Helper::isAPI()) {
                return response()->json([
                    'http_response' => 200,
                    'status' => 1,
                    'message_id' => 'Berhasil Memposting di News Feed',
                    'message' => 'News Feed Success',
                ]);
            } else {
                return redirect("$redirectTo")->with(['success' => "Berhasil Memposting di News Feed"]);
            }

        } else {
            if (str_contains(url()->current(), 'api/')) {
                return response()->json([
                    'http_response' => 400,
                    'status' => 0,
                    'message_id' => 'Gagal Memposting di News Feed',
                    'message' => 'News Feed Posting Failed',
                ]);
            } else {
                return redirect("$redirectTo")->with(['error' => "Gagal Memposting di News Feed"]);
            }
        }

    }
}
