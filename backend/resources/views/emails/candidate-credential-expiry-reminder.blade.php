@extends('emails.layouts.master')

@section('title', 'Credential Expiry Alert')
@section('preview', 'Your credential expires in ' . $daysUntilExpiry . ' day(s)')

@section('content')
    @php
        $type = $credential->credentialType?->name ?? 'Credential';
        $candidateName = $credential->candidate?->name ?? trim(($credential->candidate?->first_name ?? '') . ' ' . ($credential->candidate?->last_name ?? ''));
    @endphp

    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #111827; line-height: 1.2;">
        Credential Expiry Alert
    </h1>
    <p style="margin: 0 0 24px; font-size: 16px; color: #6b7280;">
        One of your compliance credentials is about to expire.
    </p>

    @if($daysUntilExpiry <= 5)
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 16px; text-align: center;">
                <span style="color: #DC2626; font-weight: 700;">Urgent: Renew this credential in {{ $daysUntilExpiry }} day(s).</span>
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
                        <td style="padding-bottom: 12px; font-weight: 600; color: #111827;">{{ $candidateName ?: 'Candidate' }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Credential</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $type }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Issued Date</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->issued_at?->format('F d, Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 12px;"><span style="font-size: 13px; color: #6b7280;">Expiry Date</span></td>
                        <td style="padding-bottom: 12px; color: #111827;">{{ $credential->expires_at?->format('F d, Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td><span style="font-size: 13px; color: #6b7280;">Days Remaining</span></td>
                        <td style="font-weight: 700; color: {{ $daysUntilExpiry <= 5 ? '#DC2626' : '#111827' }};">{{ $daysUntilExpiry }} day(s)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">
        Upload an updated document in your candidate portal before the expiry date to avoid compliance holds.
    </p>
@endsection

@section('footer_text', 'Automated compliance reminder from AgencHQ.')
