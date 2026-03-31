@extends('emails.layouts.master')

@section('title', 'Daily Credential Expiry Summary')
@section('preview', 'Daily summary: ' . $credentials->count() . ' credentials expiring soon')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Daily Credential Summary
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        Here's your daily summary of credentials expiring within the next 30 days.
    </p>
    
    @if($credentials->count() > 0)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 16px;">
        <tr>
            <td style="background: linear-gradient(135deg, #6D28D9 0%, #4F46E5 100%); border-radius: 12px; padding: 20px; text-align: center;">
                <span style="font-size: 32px; font-weight: 700; color: #ffffff;">{{ $credentials->count() }}</span>
                <br>
                <span style="font-size: 14px; color: rgba(255,255,255,0.8);">Credentials Expiring Soon</span>
            </td>
        </tr>
    </table>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr style="background-color: #e5e7eb;">
                        <td style="padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Candidate</td>
                        <td style="padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Position</td>
                        <td style="padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Type</td>
                        <td style="padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Expiry</td>
                        <td style="padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Status</td>
                    </tr>
                    @foreach($credentials as $credential)
                    @php
                        $daysUntilExpiry = now()->startOfDay()->diffInDays($credential->expiry_date->startOfDay(), false);
                        $statusColor = $daysUntilExpiry <= 0 ? '#DC2626' : ($daysUntilExpiry <= 7 ? '#EA580C' : ($daysUntilExpiry <= 14 ? '#D97706' : '#059669'));
                        $statusText = $daysUntilExpiry <= 0 ? 'Expired' : ($daysUntilExpiry <= 7 ? 'Urgent' : ($daysUntilExpiry <= 14 ? 'Warning' : 'Notice'));
                    @endphp
                    <tr>
                        <td style="padding: 10px 8px; font-size: 13px; color: #111827; border-top: 1px solid #e5e7eb;">{{ $credential->candidate_name }}</td>
                        <td style="padding: 10px 8px; font-size: 13px; color: #374151; border-top: 1px solid #e5e7eb;">{{ $credential->position }}</td>
                        <td style="padding: 10px 8px; font-size: 13px; color: #374151; border-top: 1px solid #e5e7eb;">{{ $credential->credential_type }}</td>
                        <td style="padding: 10px 8px; font-size: 13px; color: #374151; border-top: 1px solid #e5e7eb;">{{ $credential->expiry_date->format('M d') }}</td>
                        <td style="padding: 10px 8px; font-size: 12px; font-weight: 600; color: {{ $statusColor }}; border-top: 1px solid #e5e7eb;">{{ $statusText }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 12px; padding: 16px;">
                <span style="color: #92400E; font-weight: 600;">📋 Action Required:</span>
                <span style="color: #92400E;"> Please review and take necessary action on the credentials listed above.</span>
            </td>
        </tr>
    </table>
    @else
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 24px; text-align: center;">
                <span style="font-size: 32px;">✅</span>
                <p style="margin: 12px 0 0; color: #065F46; font-weight: 600;">Good News!</p>
                <p style="margin: 4px 0 0; color: #047857; font-size: 14px;">No credentials are expiring within the next 30 days.</p>
            </td>
        </tr>
    </table>
    @endif
@endsection

@section('footer_text', 'This is an automated daily summary from AgencHQ.')
