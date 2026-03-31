@extends('emails.layouts.master')

@section('title', 'New Login Alert')
@section('preview', 'New login detected to your AgencHQ account')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        New Login Detected
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Hi {{ $name }}, we noticed a new login to your account{{ $organizationName ? ' for ' . $organizationName : '' }}.
    </p>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 12px;">
                            <span style="font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Time</span>
                        </td>
                        <td style="padding-bottom: 12px; text-align: right; font-size: 15px; color: #111827;">
                            {{ $loggedInAt }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;">
                            <span style="font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">IP Address</span>
                        </td>
                        <td style="padding-bottom: 12px; text-align: right; font-size: 15px; color: #111827; font-family: monospace;">
                            {{ $ip }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span style="font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Device</span>
                        </td>
                        <td style="text-align: right; font-size: 14px; color: #111827;">
                            {{ $userAgent }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <p style="margin: 0 0 12px; font-size: 15px; color: #374151; line-height: 1.6;">
        If this was you, no action is needed.
    </p>
    
    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">
        If you don't recognize this login, please <a href="#" style="color: #6D28D9; text-decoration: underline;">reset your password</a> immediately.
    </p>
@endsection

@section('footer_text', 'This is an automated security alert from AgencHQ.')
