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
        'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',  // هذا الحقل للتحقق من صحة الصورة
    ]);
    //  الان نناقش الصورة 
    if($request->hasFile('image')) {
        {
        $imagePath = $request->file('image')->store('products', 'public');  // تخزين الصورة في مجلد 'products' داخل التخزين العام
        }
        }
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'] ?? null,
            'image' => $imagePath,  // تخزين مسار الصورة في قاعدة البيانات
        ]);
         return response()->json(
            [
                'success' => true,
                'message' => 'تم إنشاء المنتج بنجاح',
                'data' => $product
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
        'image'          => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
        'price'          => 'sometimes|numeric|min:0',
        'stock_quantity' => 'sometimes|integer|min:0'
    ]);

    // 3. معالجة الصورة
    if ($request->hasFile('image')) {
        // حذف الصورة القديمة
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        // حفظ الجديدة وتخزين المسار
        $validated['image'] = $request->file('image')->store('products', 'public');
    }

    // 4. التحديث
    $product->update($validated);

    // 5. الإرجاع مع refresh لضمان ظهور البيانات الجديدة
    return response()->json([
        'success' => true,
        'message' => 'تم تحديث المنتج بنجاح',
        'data'    => $product->refresh() 
    ], 200);
}


//  حذف منتج معين , هذه الخاصية متاحة للآدمن فقط
public function delete($id)
{
    //  التحقق من الصلاحية 
    if (Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح لك لحذف المنتج'], 403);
    }
    $product = Product::findOrFail($id);
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
    $product = Product::findOrFail($id);
    return response()->json([
        'success' => true,
        'message' => 'تفاصيل المنتج',
        'data'    => [
                'id'             => $product->id,
                'name'           => $product->name,
                'description'    => $product->description,
                'price'          => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'image_url'      => $product->image ? asset("storage/{$product->image}") : null,
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