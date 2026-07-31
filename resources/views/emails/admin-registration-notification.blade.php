<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>New Registration — Mind Craft Island</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#f0ebe3;font-family:'Nunito','Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0ebe3;padding:32px 16px;">
    <tr>
      <td align="center">

        {{-- Wrapper card --}}
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#FDF6EC;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.08);">

          {{-- Header --}}
          <tr>
            <td style="background-color:#E8543A;padding:36px 40px;text-align:center;">
              <p style="margin:0 0 6px;color:rgba(255,255,255,0.85);font-size:11px;letter-spacing:3px;font-weight:700;text-transform:uppercase;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                Mind Craft Island
              </p>
              <h1 style="margin:0;color:#FFFFFF;font-size:26px;font-weight:800;line-height:1.2;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                New Registration
              </h1>
              <p style="margin:10px 0 0;color:rgba(255,255,255,0.9);font-size:14px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                Submitted on {{ now()->format('D, d M Y \a\t H:i') }}
              </p>
            </td>
          </tr>

          {{-- Intro --}}
          <tr>
            <td style="padding:32px 40px 8px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
              <p style="margin:0;color:#1E2D40;font-size:15px;line-height:1.7;">
                A new participant has registered for the programme. The complete submission details are shown below.
              </p>
            </td>
          </tr>

          {{-- Section: Participant --}}
          <tr>
            <td style="padding:24px 40px 0;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
              <p style="margin:0 0 12px;color:#E8543A;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;">
                Participant Details
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="border-collapse:collapse;border:1px solid #DDD0C0;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:42%;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Registration Type
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-weight:700;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ ($data['registration_type'] ?? 'child') === 'lady' ? 'Ladies Program' : 'Child' }}
                  </td>
                </tr>
                <tr style="background-color:#FFFFFF;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:42%;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ ($data['registration_type'] ?? 'child') === 'lady' ? 'Full Name' : "Child's Full Name" }}
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-weight:700;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ $data['full_name'] }}
                  </td>
                </tr>
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Date of Birth
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ \Carbon\Carbon::parse($data['date_of_birth'])->format('d M Y') }}
                    <span style="color:#888;font-size:13px;">
                      ({{ \Carbon\Carbon::parse($data['date_of_birth'])->age }} years old)
                    </span>
                  </td>
                </tr>
                <tr style="background-color:#FFFFFF;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Fields of Interest
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ $data['field_of_interests'] ?: '—' }}
                  </td>
                </tr>
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Medical Conditions
                  </td>
                  <td style="padding:12px 16px;color:#1E2D40;font-size:15px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    @if($data['medical_conditions'])
                      <span style="color:#E8543A;font-weight:700;">{{ $data['medical_conditions'] }}</span>
                    @else
                      <span style="color:#6DBE6D;font-weight:600;">None reported</span>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Section: Contact --}}
          <tr>
            <td style="padding:24px 40px 0;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
              <p style="margin:0 0 12px;color:#E8543A;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;">
                Contact Information
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="border-collapse:collapse;border:1px solid #DDD0C0;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:42%;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Phone Number
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-weight:700;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ $data['phone_number'] }}
                  </td>
                </tr>
                @if(($data['registration_type'] ?? 'child') !== 'lady')
                <tr style="background-color:#FFFFFF;">
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Mother's Name
                  </td>
                  <td style="padding:12px 16px;border-bottom:1px solid #DDD0C0;color:#1E2D40;font-size:15px;font-weight:600;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ $data['mother_name'] ?? '—' }}
                  </td>
                </tr>
                @endif
                @if(($data['registration_type'] ?? 'child') !== 'lady')
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Emergency Contact
                  </td>
                  <td style="padding:12px 16px;color:#1E2D40;font-size:15px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    {{ $data['emergency_contact_number'] ?? '—' }}
                  </td>
                </tr>
                @endif
              </table>
            </td>
          </tr>

          {{-- Section: Consent badge --}}
          <tr>
            <td style="padding:24px 40px 0;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
              <p style="margin:0 0 12px;color:#E8543A;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;">
                Consent
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="border-collapse:collapse;border:1px solid #DDD0C0;border-radius:8px;overflow:hidden;">
                <tr style="background-color:#FDF6EC;">
                  <td style="padding:12px 16px;color:#888;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;width:42%;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    Photo / Video Consent
                  </td>
                  <td style="padding:12px 16px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                    @if($data['photo_video_consent'])
                      <span style="display:inline-block;background-color:#6DBE6D;color:#FFFFFF;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;">
                        Granted
                      </span>
                    @else
                      <span style="display:inline-block;background-color:#E8543A;color:#FFFFFF;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;">
                        Not granted
                      </span>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Divider --}}
          <tr>
            <td style="padding:32px 40px 0;">
              <hr style="border:none;border-top:1px solid #DDD0C0;margin:0;">
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background-color:#1E2D40;padding:24px 40px;text-align:center;margin-top:32px;">
              <p style="margin:0 0 4px;color:#F5C842;font-size:14px;font-weight:700;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                Mind Craft Island
              </p>
              <p style="margin:0;color:rgba(255,255,255,0.6);font-size:12px;font-family:'Nunito','Segoe UI',Arial,sans-serif;">
                This is an automated notification from the registration system.
              </p>
            </td>
          </tr>

        </table>
        {{-- End wrapper card --}}

      </td>
    </tr>
  </table>

</body>
</html>
