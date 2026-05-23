<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
//    تابع إنشاء منتج جديد من قبل اللآدمن فقط 
public function store(Request $request)
{
   // التحقق من صلاحية الآدمن 
   if(Auth::user()->role !== 'admin') {
    {
     return response()->json(
        [
            'success' => false,
            'message' => 'غير مصرح لك لإنشاء منتج'
        ], 403);
    }
    }
    // الآن نتحقق من صحة البيانات المدخلة
    $validated = $request->validate([
        'name' => 'required|string|max:100',
        'description' => 'nullable|string|max:500',
        'price' => 'required|numeric|min:0',
        'stock_quantity' => 'nullable|integer|min:0',
        'images' => 'nullable|array|max:5',
        'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
            ]);
    //  الان نناقش الصورة 
   
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'] ?? null,
        ]);
        //  معالجة رفع الصورة
                    if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $file) {
                    $product->images()->create([
                   'image_path' => $file->store('products', 'public')
                    ]);
        }
       }
         return response()->json(
            [
                'success' => true,
                'message' => 'تم إنشاء المنتج بنجاح',
                'data' => $product->load('images')
            ], 201);

}


// تابع جلب المنتجات , رح يكون متاح للجميع 
public function index()
{
    $products = Product::all();
    return response()->json(
        [
            'success' => true,
            'message'=>'إليك أهم منتجاتنا المتوفرة :' ,
            'data' => $products
        ], 200);
}

// تابع التعديل على منتج ما , هذه الخاصية متاحة للآدمن فقط

 public function update(Request $request, $id)
{
    // 1. التحقق من الصلاحية
    if (Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح لك لتحديث المنتج'], 403);
    }

    $product = Product::findOrFail($id);

    // 2. التحقق من البيانات
    $validated = $request->validate([
        'name'           => 'sometimes|string|max:100',
        'description'    => 'sometimes|nullable|string|max:500',
        'price'          => 'sometimes|numeric|min:0',
        'stock_quantity' => 'sometimes|integer|min:0',
        'images'=> 'sometimes|array|max:5',
        'images.*'=> 'image|mimes:jpeg,png,jpg,webp|max:2048'
    ]);
           //  تحديث الحقول النصية يحتفظ بالقيم القديمة إذا لم تُرسل
        $product->update([
            'name'           => $validated['name'] ?? $product->name,
            'description'    => $validated['description'] ?? $product->description,
            'price'          => $validated['price'] ?? $product->price,
            'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
        ]);
       // معالجة الصورة
    if ($request->hasFile('images')) {
      foreach ($product->images as $oldImage) {
            Storage::disk('public')->delete($oldImage->path);
            $oldImage->delete();

                 //  رفع الصور الجديدة وربطها بالمنتج
        foreach ($request->file('images') as $index => $file) {
            $product->images()->create([
                'path'=> $file->store('products', 'public'),
                'sort_order' => $index
                ]);
            }
    }
    }    
    //  الإرجاع مع refresh لضمان ظهور البيانات الجديدة
    return response()->json([
        'success' => true,
        'message' => 'تم تحديث المنتج بنجاح',
        'data'    => $product->load('images')->refresh() // تحميل الصور المحدثة مع تحديث بيانات المنتج
    ], 200);

}


//  حذف منتج معين , هذه الخاصية متاحة للآدمن فقط
public function delete($id)
{
    //  التحقق من الصلاحية 
    if (Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح لك لحذف المنتج'], 403);
    }
    $product = Product::find($id);
    if(!$product)
        {
          return response()->json(
            [
                'success'=>false,
                'message'=>'المنتج غير موجود أنه قد يكون تم حذفه مسبقاً'
            ] , 403);
        }
         // حذف الصورة من التخزين إذا كانت موجودة
    if ($product->image) {
        Storage::disk('public')->delete($product->image);    
}
//     حذف المنتج
    $product->delete();

    return response()->json([
        'success' => true,
        'message' => 'تم حذف المنتج بنجاح'
    ], 200);
}


//  تابع جلب تفاصيل منتج معين , هذه الخاصية متاحة للجميع
public function getProduct($id)
{
    $product = Product::with('images')->findOrFail($id);
    $imagesData = $product->images->map(function($image) {
        return [
            'id' => $image->id,
            'url' => asset("storage/{$image->image_path}"),
        ];
    });
    return response()->json([
        'success' => true,
        'message' => 'تفاصيل المنتج',
        'data'    => [
                'id'             => $product->id,
                'name'           => $product->name,
                'description'    => $product->description,
                'price'          => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'image_url'      => $imagesData->first() ? $imagesData->first()['url'] : null,
                'created_at'     => $product->created_at,
                'updated_at'     => $product->updated_at
    ]], 200);
}


//   تابع جلب المنتجات بالفلترة حسب اسم المنتج و الوصف و السعر 

 public function getProducts(Request $request)
 {
  $query = Product::query();
  if($request->filled('name'))
    {
    $query->where('name' , 'like' , '%' . $request->name . '%');
    }
    if($request->filled('description'))
    {
    $query->where('description' , 'like' , '%' . $request->description . '%');
    }
    if($request->filled('price'))
    {
    $query->where('price','like' , '%' . $request->price . '%');
    }
    $products = $query->get();
    return response()->json([
        'success' => true,
        'message' => 'إليك المنتجات التي تبحث عنها:',
        'data' => $products
    ], 200);
 }
}