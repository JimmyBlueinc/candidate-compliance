@extends('emails.layouts.master')

@section('title', 'Welcome to AgencHQ')
@section('preview', 'Welcome to ' . $organizationName . ' on AgencHQ')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Welcome, {{ $name }}!
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        You've been added to <strong style="color: #111827;">{{ $organizationName }}</strong> on AgencHQ.
    </p>
    
    <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6;">
        You can now access your organization's dashboard to manage candidates, job orders, placements, and more.
    </p>
    
    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">
        Click the button below to log in and get started.
    </p>
@endsection

@section('button')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="text-align: center;">
                <a href="{{ $loginUrl }}" style="display: inline-block; background: linear-gradient(135deg, #6D28D9 0%, #4F46E5 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);">
                    Log in to AgencHQ
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('footer_text', 'You received this email because an account was created for you on AgencHQ.')
