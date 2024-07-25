<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\News;
use App\Interfaces\NewsRepositoryInterface;
use App\Classes\ApiResponseClass;
use App\Http\Resources\NewsResource;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    private NewsRepositoryInterface $newsRepositoryInterface;

    public function __construct(NewsRepositoryInterface $newsRepositoryInterface)
    {
        $this->newsRepositoryInterface = $newsRepositoryInterface;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = $this->newsRepositoryInterface->index();

        return ApiResponseClass::sendResponse(NewsResource::collection($data),'',200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNewsRequest $request)
    {
        $details =[
            'name' => $request->name,
            'source' => $request->source
        ];

        DB::beginTransaction();
        try{
             $news = $this->newsRepositoryInterface->store($details);

             DB::commit();
             return ApiResponseClass::sendResponse(new NewsResource($news),'News added Successful',201);

        }catch(\Exception $ex){
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $news = $this->newsRepositoryInterface->getById($id);

        return ApiResponseClass::sendResponse(new NewsResource($news),'',200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNewsRequest $request, $id)
    {
        $updateDetails =[
            'name' => $request->name,
            'source' => $request->source
        ];
        DB::beginTransaction();
        try{
             $news = $this->newsRepositoryInterface->update($updateDetails,$id);

             DB::commit();
             return ApiResponseClass::sendResponse('News Updated Successful','',201);

        }catch(\Exception $ex){
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        $this->newsRepositoryInterface->delete($id);

        return ApiResponseClass::sendResponse('News Deleted Successful','',204);
    }
}
