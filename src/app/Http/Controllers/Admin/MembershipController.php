<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyMembershipRequest;
use App\Http\Requests\StoreMembershipRequest;
use App\Http\Requests\UpdateMembershipRequest;
use App\Models\Membership;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class MembershipController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('membership_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $membership = Membership::with(['media'])->get();

        return view('admin.memberships.index', compact('membership'));
    }

    public function create()
    {
        abort_if(Gate::denies('membership_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.memberships.create');
    }

    public function store(StoreMembershipRequest $request)
    {
        $membership = Membership::create($request->all());

        if ($request->input('image', false)) {
            $membership->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $membership->id]);
        }

        return redirect()->route('admin.memberships.index');
    }

    public function edit(Membership $membership)
    {
        abort_if(Gate::denies('membership_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.memberships.edit', compact('membership'));
    }

    public function update(UpdateMembershipRequest $request, Membership $membership)
    {
        $membership->update($request->all());

        if ($request->input('image', false)) {
            if (! $membership->image || $request->input('image') !== $membership->image->file_name) {
                if ($membership->image) {
                    $membership->image->delete();
                }
                $membership->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
            }
        } elseif ($membership->image) {
            $membership->image->delete();
        }

        return redirect()->route('admin.memberships.index');
    }

    public function show(Membership $membership)
    {
        abort_if(Gate::denies('membership_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.memberships.show', compact('membership'));
    }

    public function destroy(Membership $membership)
    {
        abort_if(Gate::denies('membership_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $membership->delete();

        return back();
    }

    public function massDestroy(MassDestroyMembershipRequest $request)
    {
        $memberships = Membership::find(request('ids'));

        foreach ($memberships as $membership) {
            $membership->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('membership_create') && Gate::denies('membership_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Membership();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
