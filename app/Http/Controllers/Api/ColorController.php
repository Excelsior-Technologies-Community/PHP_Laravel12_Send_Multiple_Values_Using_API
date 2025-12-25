<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    // GET: All Colors
    public function index()
    {
        return response()->json(Color::all(), 200);
    }

    // POST: Create Color
    public function store(Request $request)
    {
        $request->validate([
            'color_name' => 'required|unique:colors,color_name',
        ]);

        $color = Color::create([
            'color_name' => $request->color_name
        ]);

        return response()->json($color, 201);
    }

    // GET: Single Color
    public function show($id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }
        return response()->json($color, 200);
    }

    // PUT/PATCH: Update Color
    public function update(Request $request, $id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $request->validate([
            'color_name' => 'required|unique:colors,color_name,' . $id,
        ]);

        $color->update([
            'color_name' => $request->color_name
        ]);

        return response()->json($color, 200);
    }

    // DELETE: Remove Color
    public function destroy($id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $color->delete();
        return response()->json(['message' => 'Color deleted'], 200);
    }
}
