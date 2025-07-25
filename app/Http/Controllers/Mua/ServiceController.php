<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Auth::user()->services;
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration'     => 'required|string|max:50',
            'photo'        => 'nullable|image|max:2048',
            'makeup_style' => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:100',
        ]);

        $data = $request->only(['name', 'description', 'price', 'duration', 'makeup_style', 'category']);
        $data['mua_id'] = Auth::id();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('service_photos');
            $data['photo'] = asset(str_replace('public', 'storage', $path));
        }

        $service = Service::create($data);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();

        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration'     => 'required|string|max:50',
            'photo'        => 'nullable|image|max:2048',
            'makeup_style' => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:100',
        ]);

        $data = $request->only(['name', 'description', 'price', 'duration', 'makeup_style', 'category']);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('service_photos');
            $data['photo'] = asset(str_replace('public', 'storage', $path));
        }

        $service->update($data);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => $service
        ]);
    }

    public function destroy($id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();
        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }
}
