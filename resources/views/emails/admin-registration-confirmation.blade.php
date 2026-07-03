@php
    $isAr  = $locale === 'ar';
    $dir   = $isAr ? 'rtl' : 'ltr';
    $align = $isAr ? 'right' : 'left';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{ $isAr ? 'تأكيد التسجيل — جزيرة مايند كرافت' : 'Registration Confirmation — Mind Craft Island' }}</title>
  @if($isAr)
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  @else
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  @endif
</head>
<body style="margin:0;padding:0;background-color:#f0ebe3;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;direction:{{ $dir }};">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0ebe3;padding:32px 16px;" dir="{{ $dir }}">
    <tr>
      <td align="center">

        {{-- Wrapper card --}}
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#FDF6EC;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.08);">

          {{-- Header --}}
          <tr>
            <td style="background-color:#1E2D40;padding:36px 40px;text-align:center;">
              <p style="margin:0 0 4px;color:#F5C842;font-size:11px;letter-spacing:3px;font-weight:700;text-transform:uppercase;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
                {{ $isAr ? 'جزيرة مايند كرافت' : 'Mind Craft Island' }}
              </p>
              <h1 style="margin:0;color:#FFFFFF;font-size:26px;font-weight:800;line-height:1.3;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
                {{ $isAr ? 'تأكيد التسجيل' : 'Registration Confirmation' }}
              </h1>
            </td>
          </tr>

          {{-- Greeting --}}
          <tr>
            <td style="padding:36px 40px 0;text-align:{{ $align }};font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
              <p style="margin:0 0 8px;color:#1E2D40;font-size:18px;font-weight:700;">
                {{ $isAr ? 'السيدة ' . $data['mother_name'] . '،' : 'Dear ' . $data['mother_name'] . ',' }}
              </p>
              <p style="margin:0;color:#444;font-size:15px;line-height:1.8;">
                @if($isAr)
                  يسعدنا إعلامكم بأنّ تسجيل طفلكم/طفلتكم في برنامج <strong>جزيرة مايند كرافت</strong> قد تمّ بنجاح.
                  سيتواصل معكم فريقنا قريباً لتزويدكم بجميع التفاصيل اللازمة.
                @else
                  We are pleased to confirm that your child's registration with
                  <strong>Mind Craft Island</strong> has been successfully received.
                  Our team will be in touch shortly with all the necessary details.
                @endif
              </p>
            </td>
          </tr>

          {{-- Registration summary box --}}
          <tr>
            <td style="padding:28px 40px 0;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="border-collapse:collapse;border:1px solid #DDD0C0;border-radius:8px;overflow:hidden;background-color:#FFFFFF;">
                <tr>
                  <td colspan="2" style="padding:14px 16px;background-color:#E8543A;">
                    <p style="margin:0;color:#FFFFFF;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
                      {{ $isAr ? 'ملخص التسجيل' : 'Registration Summary' }}
                    </p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:42%;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $isAr ? 'اسم الطفل' : "Child's Name" }}
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-weight:700;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $data['full_name'] }}
                  </td>
                </tr>
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $isAr ? 'تاريخ الميلاد' : 'Date of Birth' }}
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ \Carbon\Carbon::parse($data['date_of_birth'])->format('d M Y') }}
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $isAr ? 'رقم الهاتف' : 'Phone Number' }}
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $data['phone_number'] }}
                  </td>
                </tr>
                @if($data['field_of_interests'])
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $isAr ? 'الحقول المهمة' : 'Fields of Interest' }}
                  </td>
                  <td style="padding:12px 16px;color:#1E2D40;font-size:15px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;text-align:{{ $align }};">
                    {{ $data['field_of_interests'] }}
                  </td>
                </tr>
                @endif
              </table>
            </td>
          </tr>

          {{-- Closing note --}}
          <tr>
            <td style="padding:28px 40px 36px;text-align:{{ $align }};font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
              <p style="margin:0 0 16px;color:#444;font-size:15px;line-height:1.8;">
                @if($isAr)
                  إن كان لديكم أي استفسار، فلا تترددوا في التواصل معنا.
                  <br>نتطلع إلى الترحيب بطفلكم/طفلتكم في عائلة مايند كرافت!
                @else
                  If you have any questions, please do not hesitate to contact us.
                  <br>We look forward to welcoming your child to the Mind Craft family!
                @endif
              </p>
              <p style="margin:0;color:#1E2D40;font-size:15px;font-weight:700;">
                {{ $isAr ? 'مع أطيب التحيات،' : 'Warm regards,' }}<br>
                <span style="color:#E8543A;">
                  {{ $isAr ? 'فريق جزيرة مايند كرافت' : 'The Mind Craft Island Team' }}
                </span>
              </p>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background-color:#1E2D40;padding:20px 40px;text-align:center;">
              <p style="margin:0 0 4px;color:#F5C842;font-size:13px;font-weight:700;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
                Mind Craft Island
              </p>
              <p style="margin:0;color:rgba(255,255,255,0.55);font-size:12px;font-family:{{ $isAr ? "'Cairo'" : "'Nunito'" }},'Segoe UI',Arial,sans-serif;">
                {{ $isAr ? 'هذه رسالة تأكيد تلقائية — يُرجى عدم الرد عليها.' : 'This is an automated confirmation — please do not reply.' }}
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
