@extends('emails.layouts.master')

@section('title', 'Your Login Code')
@section('preview', 'Your verification code for ' . $organizationName)

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Your Verification Code
    </h1>
    <p style="margin: 0 0 32px; font-size: 16px; color: #6b7280;">
        Use this code to access <strong style="color: #111827;">{{ $organizationName }}</strong> on AgencHQ.
    </p>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="text-align: center; background: linear-gradient(135deg, #6D28D9 0%, #4F46E5 100%); border-radius: 16px; padding: 32px;">
                <span style="font-size: 40px; font-weight: 700; color: #ffffff; letter-spacing: 8px; font-family: 'Courier New', monospace;">
                    {{ $code }}
                </span>
            </td>
        </tr>
    </table>
    
    <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6; text-align: center;">
        This code expires in <strong>{{ $expiresMinutes }} minutes</strong>.
    </p>
    
    <p style="margin: 0; font-size: 14px; color: #9ca3af; line-height: 1.6; text-align: center;">
        If you didn't request this code, you can safely ignore this email.
    </p>
@endsection

@section('footer_text', 'This is an automated message from AgencHQ. Do not reply to this email.')
