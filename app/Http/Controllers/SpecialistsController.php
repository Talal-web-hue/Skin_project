<?php

namespace App\Http\Controllers;

// use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use App\Models\Specialist;
use App\Models\Specialists;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SpecialistsController extends Controller
{
 public function store(Request $request)
    {
        // 🔒 التحقق من صلاحية المدير
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        // ✅ التحقق من المدخلات (مطابق لهيكلك الجديد)
        $validated = $request->validate([
            'first_name'     => 'required|string|max:50',
            'last_name'      => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|unique:users,phone|regex:/^[0-9]{8,15}$/',
            'password'       => 'required|string|min:6',
            'date_of_birth'  => 'nullable|date',
            'skin_type'      => 'nullable|in:dry,oily', // ✅ جعلناه اختياريًا
            'specialization' => 'required|string|max:250',
            'bio'            => 'required|string|max:500',
            'is_active'      => 'sometimes|boolean',
        ]);

        try {
            $specialist = DB::transaction(function () use ($validated) {
                // 1️⃣ إنشاء حساب المستخدم
                $user = User::create([
                    'first_name'    => $validated['first_name'],
                    'last_name'     => $validated['last_name'],
                    'email'         => $validated['email'],
                    'phone'         => $validated['phone'],
                    'password'      => Hash::make($validated['password']),
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'role'          => 'specialist', // ✅ الآن متوافق مع الـ ENUM
                    'skin_type'     => $validated['skin_type'] ?? null, // ✅ المعامل ?? يمنع الخطأ نهائيًا
                ]);

                // 2️⃣ إنشاء سجل الأخصائي وربطه بالمستخدم
                // ✅ نستخدم $user->id لأن الـ Migration يستخدم $table->id() الافتراضي
                $specialist = Specialists::create([
                    'user_id'        => $user->id,
                    'specialization' => $validated['specialization'],
                    'bio'            => $validated['bio'],
                    'is_active'      => $validated['is_active'] ?? true,
                    'rating'         => null, // يُحسب تلقائيًا لاحقًا
                ]);

                // إرجاع البيانات مع معلومات المستخدم الأساسية
                return $specialist->load('user:id,first_name,last_name,email,phone,role,skin_type');
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء حساب الأخصائي بنجاح',
                'data'    => $specialist
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في الإنشاء: ' . $e->getMessage()
            ], 500);
        }
    }


    // تحديث بيانات الأخصائي
public function update(Request $request, $id)
{
    // 1. التحقق من صلاحية المدير
    if (Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
    }

    // 2. جلب الأخصائي والمستخدم المرتبط به
    $specialist = Specialists::with('user')->findOrFail($id);

    // 3. التحقق من البيانات المرسلة
    $validated = $request->validate([
        'first_name'     => 'sometimes|string|max:50',
        'last_name'      => 'sometimes|string|max:50',
        'email'          => "sometimes|email|unique:users,email,{$specialist->user_id},user_id",
        'phone'          => "sometimes|unique:users,phone,{$specialist->user_id},user_id",
        'password'       => 'sometimes|string|min:6',
        'date_of_birth'  => 'nullable|date',
        'skin_type'      => 'nullable|in:dry,oily',
        'specialization' => 'sometimes|string|max:250',
        'bio'            => 'sometimes|string|max:500',
        'is_active'      => 'sometimes|boolean',
    ]);

    try {
        // 4. تحديث جدول المستخدمين (البيانات الشخصية)
        // نستخدم merge لدمج كلمة المرور المشفرة إذا وجدت
        $userData = $validated;
        if (isset($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }
        $specialist->user->update($userData);

        // 5. تحديث جدول الأخصائيين (البيانات المهنية)
        $specialist->update([
            'specialization' => $validated['specialization'] ?? $specialist->specialization,
            'bio'            => $validated['bio'] ?? $specialist->bio,
            'is_active'      => $validated['is_active'] ?? $specialist->is_active,
        ]);

        // 6. إرجاع النتيجة
        return response()->json([
            'success' => true,
            'message' => 'تم التحديث بنجاح',
            'data'    => $specialist->load('user')
        ]);

    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

//   تابع جلب بيانات الأخصائي مع بيانات المستخدم المرتبط 
 
public function getSpecialist($id)
{
      if (Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
    }

  $specialist = Specialists::with('user:id,first_name,last_name,phone,email,date_of_birth')
  ->where('id' , $id)->first();
  if(!$specialist)
    {
    return response()->json(
        [
            'message'=>'الأخصائي غير موجود أو متاح حاليا '
        ] , 401);   
    }
    // $specialistInfo= $specialist->get();
    return response()->json(
        [
            'message'=>'date of the specialist is:' , 
            'specialist_id'=>$specialist->id,
            'specialization'=>$specialist->specialization,
            'bio'=>$specialist->bio,
            'user'=>$specialist->user  // بيانات المستخدم المرتبط
            ] , 200);
}


// جلب الأخصائيين النشطين مع فلترة بسيطة 

 public function index(Request $request)  // طبعا قمنا بتمرير ريكوست للتابع من اجل عملية الفلترة 
 {
  $query = Specialists::with('user:id,first_name,last_name,phone')
  ->where('is_active',true);
//   الآن الفلترة حسب التخصص
 if($request->filled('specialization'))
    {
     $query->where('specialization','like','%'.$request->specialization.'%');
    }

    $specialists = $query->get();
    return response()->json(
        [
            'message'=>'إليك قائمة الأخصائيين النشطين:',
            'success'=>'true',
            'data'=>$specialists
        ] , 200);
 }

}
       
