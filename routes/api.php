<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpecialistsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Termwind\Components\Raw;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//  User Api

Route::post('register' , [UserController::class , 'register']);
Route::post('login' , [UserController::class , 'login']);
Route::post('logout' , [UserController::class , 'logout'])->middleware('auth:sanctum');
Route::get('getInfoUser' , [UserController::class , 'profile'])->middleware('auth:sanctum');  // للآدمن جلب حساب المستخدم أيضا 
Route::delete('deleteUser/{userId}' , [UserController::class , 'delete'])->middleware('auth:sanctum');
Route::put('updateUser/{userId}' , [UserController::class , 'update'])->middleware('auth:sanctum');
Route::post('changePassword' , [UserController::class , 'changePassword'])->middleware('auth:sanctum');


//  Specialist Api

Route::post('storeSpecialist' , [SpecialistsController::class , 'store'])->middleware('auth:sanctum');
Route::put('updateSpecialist/{specialistId}' , [SpecialistsController::class , 'update'])->middleware('auth:sanctum');
Route::delete('destroySpecialist/{specialistId}' , [SpecialistsController::class , 'destroy'])->middleware('auth:sanctum');
Route::get('getSpecialist/{specialistId}' , [SpecialistsController::class , 'getSpecialist'])->middleware('auth:sanctum');
Route::get('index' , [SpecialistsController::class , 'index']); // لجلب الأخصائيين النشطين مع الفلترة حسب التخصص
//  طبعا طريقة عامة لا تحتاج إلى حماية


// Service API
Route::post('createService' , [ServiceController::class , 'store'])->middleware('auth:sanctum');  // أعطيتها حمابة لأن الأدمن فقط من يقوم بعملية إنشاء الخدمة
Route::get('indexService' , [ServiceController::class , 'index']);   // عام من أجل مشاهدةالخدمات المتاحة في موقعي
Route::put('updateService/{id}' , [ServiceController::class , 'update'])->middleware('auth:sanctum');   // الآدمن هو الذي يقوم بتحديث الخدمات فقط
Route::delete('destroyService/{id}' , [ServiceController::class , 'destroy'])->middleware('auth:sanctum');
Route::get('searchService' , [ServiceController::class , 'search']);  // لا يحتاج إلى حماية لأنه عام و يقوم به جميع المستخدمين 


// Appointment API
/* من يقوم بعملية الحجز 
- الزبون يقوم بالحجز عبر الموقع 
- الزبون لا يمكنه حجز موعد لغيره
- الإدارة أو الاستقبال اي الأدمن يقوم بالحجز عبر ايضا عبر الاتصال الهاتفي او زيارة المركز بشكل مباشر
- الأخصائي يدير جدول المواعيد, اي يغير الحالة من معلقة إلى مؤكدة
يمنع حجز نفس الأخصائي اذا الوقت الجديد يتداخل مع اي موعد غير ملغي
*/
Route::post('storeAppointment' , [AppointmentController::class , 'store'])->middleware('auth:sanctum');
Route::get('myAppointment' , [AppointmentController::class , 'myAppointment'])->middleware('auth:sanctum');
Route::get('specialistAppointment' , [AppointmentController::class , 'specialistAppointment'])->middleware('auth:sanctum');
Route::get('allAppointmentForAdmin' , [AppointmentController::class , 'allAppointmentForAdmin'])->middleware('auth:sanctum')->middleware('auth:sanctum');
Route::post('appointment/{id}/cancel' , [AppointmentController::class , 'cancel'])->middleware('auth:sanctum')->middleware('auth:sanctum');

// Order API
Route::post('storeOrder', [OrderController::class, 'store'])->middleware('auth:sanctum');
Route::get('orders', [OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('orders/{id}', [OrderController::class, 'show'])->middleware('auth:sanctum');
Route::put('admin/ordersUpdate/{orderId}', [OrderController::class, 'orderUpdateStatus'])->middleware('auth:sanctum');
Route::('order/{id}/cancel' , [OrderController::class , 'cancelOrder'])->middleware('auth:sanctum');   // من أجل إلغاء الطلب الخاص بالزبون و إعادة المنتج للمخزون 

