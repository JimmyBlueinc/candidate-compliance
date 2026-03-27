@extends('emails.layouts.master')

@section('title', 'Credential Expiring Soon')
@section('preview', 'A credential for ' . $credential->candidate_name . ' expires in ' . $daysUntilExpiry . ' days')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Credential Expiry Reminder
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        A credential is expiring soon and requires your attention.
    </p>
    
    @if($daysUntilExpiry <= 7)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 16px; text-align: center;">
                <span style="color: #DC2626; font-weight: 600;">⚠️ Urgent: This credential expires in {{ $daysUntilExpiry }} day(s)</span>
            </td>
        </tr>
    </table>
    @endif
    
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 12px; width: 40%;"><span style="font-size: 13px; color: #6b7280;">Candidate</span></td>
                        <td style="padding-bottom: 12px; font-weight: 600; color: #111827;">{{ $credential->candidate_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Position</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->position }}</td>
                    </tr>
                    @if($credential->specialty)
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Specialty</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->specialty }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Credential Type</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->credential_type }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Email</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->email }}</td>
                    </tr>
                    @if($credential->province)
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Province</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->province }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Issue Date</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->issue_date->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Expiry Date</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->expiry_date->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><span style="font-size: 13px; color: #6b7280;">Days Until Expiry</span></td>
                        <td style="font-weight: 600; color: {{ $daysUntilExpiry <= 7 ? '#DC2626' : '#111827' }};">{{ $daysUntilExpiry }} day(s)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">
        Please review and renew this credential before it expires.
    </p>
@endsection

@section('footer_text', 'This is an automated reminder from AgencHQ.')
