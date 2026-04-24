<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialists;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

use function Symfony\Component\Clock\now;

class AppointmentController extends Controller
{
    // حجز موعد 
    public function store(Request $request)
    {
    $user = Auth::user();   // تقوم بجلب المستخدم الحالي
    $validated = $request->validate(
        [
            'specialist_id'=>'required|exists:specialists,id',
            'service_id'=>'required|exists:services,id',
            'start_date'=>'required|date:after:now',
            // 'end_date'=>'required|' ,  // لا حاجة لإرساله من الواجهة , يعتمد على حقل duration من جدول الخدمات
            // الزبون يحجز لنفسه تلقائيا إذا كان الأدمن يمكنه تحديد رقم المستخدم
           'user_id'=>$user->role === 'admin' ? 'sometimes|exists:users,id' : 'prohibited'
            ]);

            // حساب وقت الانتهاء بناءا على مدة الخدمة 
            $service = Service::findOrFail($validated['service_id']);
            $start = Carbon::parse($validated['start_date']);
            $end = $start->copy()->addMinutes($service->duration);

            // الآن منع التعارض الزمني , أي اذا كان نفس الأخصائي و الوقت متداخل و غير ملغي
            $conflict = Appointment::where('specialist_id' , $validated['specialist_id'])
            ->whereNotIn('status' , ['cancelled'])   // يسمح بالحجز في أوقات كانت محجوزة سابقا لكن تم إلغائها
            ->where(function ($q) use ($start , $end){
             $q->whereBetween('start_date' , [$start , $end])
             ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                  });
            })->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'الأخصائي محجوز في هذا الوقت. يرجى اختيار وقت آخر.'
            ], 409);
        }
        
        //  إنشاء الموعد
        $appointment = Appointment::create(
            [
                'user_id'=>$user->role === 'admin' ? ($validated['user_id'] ?? $user->id) : $user->id,
                'specialist_id'=>$validated['specialist_id'],  
                'service_id'=>$validated['service_id'], 
                'start_date'=>$validated['start_date'], 
                'end_date'=>$end,
                'status'=>'pending' 
            ]);
      return response()->json([
      'success' => true,
    'message' => 'تم حجز الموعد بنجاح',
    'data'    => $appointment->load([
        'service' => function ($q) { $q->select('id', 'name', 'duration', 'price'); },
        'specialist.user' => function ($q) { $q->select('id', 'first_name', 'last_name'); }
    ])
], 201);
    }
     
    //  جلب مواعيد العميل (اي الزبون)
    public function myAppointment(Request $request)
     {
      $query = Appointment::where('user_id' , Auth::id())
      ->with([
        'service:id,name,duration,price',
        'specialist.user:id,first_name,last_name'   // اي جلب الموعد مع الخدمة مع الأخصائي
      ])->orderBy('start_date' , 'desc');

      if($request->filled('status'))    // اي جلب الموعد حسب حالته ,, اي اذا المستخدم ادخل الحالة انها ملغاة يقوم بجلب جميع المواعيد الملغاة
        {   
         $query->where('status' , $request->status);
        }

        return response()->json(
            [
                'success'=>true, 
                'data'=>$query->paginate(5)  // اي كل مواعيد للعميل يتم عرضها في صفحة واحدة
            ]);
     }


    //  جلب مواعيد الاخصائي

    public function specialistAppointment()
    {   
     if(Auth::user()->role !=='specialist')
        {
        return response()->json(
            [
                'success'=>true ,
                'message'=>'غير مصرح لك'
            ] , 403);
        }
        // هنا قمنا بجلب سجل الأخصائي مشان نجيب المواعيد الخاصة فيه
     $specialist = Specialists::where('user_id' , Auth::id())->firstOrFail();
     $query = Appointment::where('specialist_id' ,$specialist->id)
     ->with([
        'user:id,first_name,last_name,phone,date_of_birth' ,
        'service:id,name,duration'
     ])->orderBy('start_date' , 'desc');

     return response()->json(
        [
            'success'=>true,
            'data'=>$query->get()
        ] , 200);
    
     }


    //   لرؤية كل الحجوزات , طبعا الصلاحية هي للأدمن فقط
    public function allAppointmentForAdmin()
    {
        if(Auth::user()->role !=='admin')
         {
         return response()->json(
            [
                'success'=>false,
                'message'=>'غير مصرح لك , هذه الصفحة للإدارة فقط'
            ] , 403);
         }
            $query = Appointment::with(
                [
                    'user:id,first_name,last_name,phone,email',
                    'specialist.user:id,first_name,last_name',
                    'service:id,name,duration,price'
                ]);

                $allAppointments = $query->get();
                return response()->json(
                    [
                        'success'=>true ,
                        'message'=>'إليكم جميع المواعيد المتاحة',
                        'data'=>$allAppointments
                    ] , 200);
         }
    


        //   تابع إلغاء الموعد من قِبل العميل , مع منع إلغاء المواعيد المنجزة
        public function cancel(Request $request , $appointmentId)
        {
        $user = Auth::user();
        $appointment = Appointment::findOrFail($appointmentId);
        //  الآن نتحقق من ملكية الموعد , اي هل الموعد ينتمي لهذا العميل ام لا
        if($appointment->user_id !==$user->id)  
            {
            return response()->json(
                [
                    'success'=>false,
                    'message'=>'غير مصرح لك بإلغاء هذا الموعد'
                ] , 409);
            }
            //  الآن حالة منع الإلغاء إذا كان ملغياً أو مكتملاً
            if(in_array($appointment->status, ['canclled' , 'completed']))
                {
                return response()->json(
                    [
                        'success'=>false,
                        'message'=>'لا يمكن إلغاء موعد مكتمل او ملغي سابقاً'
                    ] , 409);     
                }
                //  الآن حالة الإلغاء قبل 24 ساعة على الأقل من موعد البداية
                if($appointment->start_date < Carbon::now()->addHours(24))
                    {
                    return response()->json(
                        [
                            'success'=>false,
                            'message'=>'عذرا: لا يمكن الإلغاء قبل أقل من 24 ساعة ,, يرجى التواصل مع الإدارة' ,

                        ] , 403);
                    }
                    //   في حال لم يتم تنفيذ أي من الحالات السابقة عندها يتم إلغاء الموعد 
                    $appointment->update(['status'=>'cancelled']);
                    return response()->json(
                        [
                            'success'=>true ,
                            'message'=>'تم إلغاء الموعد بنجاح'
                        ] , 200);   // طبعا مشان نعرف انه تم الإلغاء اثناء عرض جميع المواعيد المتاحة يعطيني جانب الموعد الملغي cancelled
        }
    }
