@extends('emails.layouts.master')

@section('title', 'Welcome to the Talent Network')
@section('preview', 'You\'ve joined ' . $organizationName . '\'s talent network on AgencHQ')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Welcome to the Talent Network!
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Hi{{ $name ? ' ' . $name : '' }}, thanks for joining <strong style="color: #111827;">{{ $organizationName }}</strong>'s talent network.
    </p>
    
    @if($tempPassword)
    <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6;">
        Your account has been created. Use the temporary password below to log in:
    </p>
    
    <div style="margin: 0 0 24px; padding: 16px; background: #f3f4f6; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
            Temporary Password
        </p>
        <p style="margin: 0; font-size: 24px; font-weight: 700; color: #111827; font-family: monospace; letter-spacing: 2px;">
            {{ $tempPassword }}
        </p>
    </div>
    
    <p style="margin: 0 0 24px; font-size: 14px; color: #dc2626; line-height: 1.6;">
        ⚠️ You will be required to change this password when you first log in.
    </p>
    @else
    <p style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6;">
        You'll now receive notifications about new job opportunities that match your skills and preferences.
    </p>
    
    <p style="margin: 0 0 24px; font-size: 15px; color: #374151; line-height: 1.6;">
        Complete your profile to increase your chances of being matched with the perfect position.
    </p>
    @endif
@endsection

@section('button')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="text-align: center;">
                <a href="{{ $loginUrl ?? $profileUrl }}" style="display: inline-block; background: linear-gradient(135deg, #6D28D9 0%, #4F46E5 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);">
                    {{ $tempPassword ? 'Log In Now' : 'Complete Your Profile' }}
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('footer_text', 'You received this email because you joined ' . $organizationName . '\'s talent network on AgencHQ.')
