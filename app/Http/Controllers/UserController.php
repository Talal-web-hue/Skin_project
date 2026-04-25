<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Termwind\Components\Raw;

class UserController extends Controller
{
   public function register(Request $request)
   {
    $user = $request->validate(
      [
         'first_name'=>'required|string|max:50', 
         'last_name'=>'required|string|max:50', 
         'email'=>'required|string|unique:users,email', 
         'password'=>'required|string|min:6|confirmed',
         'phone'=>'required|string|max:15', 
         'date_of_birth'=>'nullable|date',
         'skin_type'=>'required|in:dry,oily',
         'role'=>'sometimes|in:client,admin,specialist' , 
      ]);

      $user = User::create(
         [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'email'=>$request->email,
            'phone'=>$request->phone,
            'date_of_birth'=>$request->date_of_birth,
            'skin_type'=>$request->skin_type,
            'password'=>Hash::make($request->password),
            'role'=>$request->role   // الافتراضي أن المستخدم هو زبون
         ]);

         return response()->json(
            [
               'message'=>'تم إنشاء الحساب بنجاح' ,
               'user'=>$user        
            ] , 201);
   }



   //  تسجيل الدخول
    
   public function login(Request $request)
   {
    $request->validate(
      [
     'email'=>'required|email',
     'password'=>'required|string'
      ]);
      
      if(!Auth::attempt($request->only(['email','password'])))
         {
         return response()->json(
            [
               'message'=>'invalid email or password'
            ] , 401);
         }
         $user = User::where('email' , $request->email)->first();  
         $token = $user->createToken('auth_token')->plainTextToken;
         return response()->json(
            [
               'message'=>'login successfully' ,
               'user'=>$user,
               'token'=>$token
            ] , 201);
   }



   public function logout(Request $request)
   {
    $user = Auth::user();
    $request->user()->currentAccessToken()->delete();
    return response()->json(
      [
         'message'=>'تم تسجيل الخروج بنجاح'
      ] , 200);
   }


   //  لجلب معلومات المستخدم 
   public function profile()
   {
     $user = Auth::user();
      return response()->json(
         [
            'message'=>'معلوماتي الحالية هي:',
            'user'=>$user     // تعيد معلومات الحساب
         ] , 200);
      
   }

   //  تحديث بيانات المستخدم للزبون نفسه أو الأدمن 
   public function update(Request $request , $id)
   {
    $user = User::findOrFail($id);  // مشان نشوف هل المستخدم يلي بدنا نعدل عليه هل هو موجود أم
    $authUser = Auth::user();
    if($authUser->role !=='admin' && $authUser->getKey() != $user->getKey())   // طبعا الخطأ هنا لا يؤثر بشي و سببه عدم وجود ميزة مفعلة في الفيجوال
      {
       return response()->json(
         [
            'success'=>false,
            'message'=>'غير مصرح'
         ] , 403);
      }

      $validated = $request->validate(
         [
            'first_name'    => 'sometimes|string|max:50',
            'last_name'     => 'sometimes|string|max:50',
            'email'         => 'sometimes|email|unique:users,email',
            'phone'         => 'sometimes|unique:users,phone',
            'password'      => 'sometimes|string|min:6',
            'date_of_birth' => 'nullable|date',
            'skin_type'     => 'nullable|in:dry,oily'
         ]);
         //  تشفير كلمة المرور إذا تم إرسالها
         if(isset($validated['password']))
            {
            $validated['password'] = Hash::make($validated['password']);
            }
         $user->update($validated);
            return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'data'    => $user]);
   }

   // حذف المستخدم للأدمن فقط و المستخدم نفسه 

   public function delete($id)
   {
    if(Auth::user()->role !=='admin')
      {
      return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
      }

      $user = User::findOrFail($id);
      //  نحط شرط انه لا يمكن حذف المستخدم إذا كانت له مواعيد نشطة
      if($user->appointments()->whereNotIn('status', ['cancelled' , 'completed'])->exists())
         {
          return response()->json([
            'success'=>false,
            'message'=>'لا يمكن حذف مستخدم لديه مواعيد قادمة أو قيد التنفيذ'
          ] , 403);
         }
         $user->delete();
         return response()->json(
            [
               'success'=>true , 
               'message'=>'تم حذف المستخدم بنجاح'
            ], 200);
   }


   //  تابع تغيير كلمة السر للمستخدم العادي , اي الزبون

   public function changePassword(Request $request)
   {
    $user = Auth::user();
    $validated = $request->validate(
      [
         'current_password'=>'required|string',
         'new_password'=>'required|string|min:6|confirmed',
      ]);
      //  الآن نتحقق من صحة كلمة المرور الحالية
      if(!Hash::check($validated['current_password'] , $user->password) )
         {
          return response()->json(
            [
             'success'=>false,
             'message'=>'كلمة المرور الحالية غير صحيحة'
            ] , 403);
         }

         //  الآن نقوم بتحديث كلمة المرور 
       $user->update([                          // طبعا هذا ليس حطأ
            'password' => Hash::make($validated['new_password'])
        ]);
        
      return response()->json(
         [
            'success'=>true,
            'message'=>'تم تغيير كلمة المرور بنجاح , يرجى تسجيل الدخول مرة أخرى في الأجهزة الأخرى'
         ]);
  }
}
