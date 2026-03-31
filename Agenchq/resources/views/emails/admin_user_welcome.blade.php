@extends('emails.layouts.master')

@section('title', 'Your Admin Account')
@section('preview', 'You have been granted admin access for ' . $organizationName)

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Admin Access Granted
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        You've been granted admin access for <strong style="color: #111827;">{{ $organizationName }}</strong> on AgencHQ.
    </p>
    
    <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6;">
        Here are your login credentials:
    </p>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 12px;">
                            <span style="font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Email</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px; font-weight: 600; color: #111827; padding-bottom: 16px;">
                            {{ $email }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;">
                            <span style="font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Temporary Password</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px; font-weight: 600; color: #111827; font-family: monospace;">
                            {{ $tempPassword }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">
        You'll be asked to change your password on first login.
    </p>
@endsection

@section('button')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="text-align: center;">
                <a href="{{ $loginUrl }}" style="display: inline-block; background: linear-gradient(135deg, #6D28D9 0%, #4F46E5 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);">
                    Sign in to AgencHQ
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('footer_text', 'If you did not expect this, contact your organization owner.')
