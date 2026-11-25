<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation Acknowledgement Certificate — {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 20mm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .cert-border {
            border: 4px double #059669;
            padding: 24px;
            border-radius: 8px;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #059669;
            font-size: 26px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 6px 0;
        }
        .header .subtitle {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cert-number {
            text-align: right;
            font-size: 11px;
            color: #64748b;
            margin-bottom: 16px;
        }
        .cert-title {
            text-align: center;
            font-size: 20px;
            color: #0f172a;
            font-weight: 700;
            margin: 20px 0 10px 0;
        }
        .cert-body {
            font-size: 13px;
            line-height: 1.6;
            text-align: justify;
            color: #334155;
            margin-bottom: 24px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 24px 0;
            font-size: 12px;
        }
        .details-table th, .details-table td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .details-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            width: 32%;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .footer-table td {
            vertical-align: top;
            padding: 8px;
        }
        .qr-section {
            text-align: center;
        }
        .qr-section img {
            width: 110px;
            height: 110px;
        }
        .qr-section small {
            display: block;
            color: #64748b;
            font-size: 9px;
            margin-top: 4px;
        }
        .signature-block {
            text-align: right;
            padding-top: 30px;
        }
        .signature-line {
            display: inline-block;
            border-top: 1px solid #0f172a;
            width: 200px;
            padding-top: 6px;
            text-align: center;
            font-size: 11px;
            color: #334155;
        }
        .disclaimer {
            font-size: 9.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 24px;
            text-align: center;
            line-height: 1.4;
        }
        .badge-verified {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="cert-border">
        <div class="header">
            <h1>Food Waste Rescue</h1>
            <div class="subtitle">Surplus Food Rescue & Redistribution Network</div>
        </div>

        <div class="cert-number">
            <strong>Certificate ID:</strong> {{ $certificate->certificate_number }}<br>
            <strong>Issued Date:</strong> {{ $certificate->issued_at->format('M d, Y') }}
        </div>

        <div class="cert-title">DONATION ACKNOWLEDGEMENT CERTIFICATE</div>

        <div class="cert-body">
            This certificate officially acknowledges that <strong>{{ $restaurant->restaurant_name ?? 'The Donor Restaurant' }}</strong> has generously provided surplus food through the Food Waste Rescue network. The rescued food was securely handed over, verified, and successfully distributed to communities in need by verified partner NGO <strong>{{ $ngo->ngo_name ?? 'Partner NGO' }}</strong>.
        </div>

        <table class="details-table">
            <tr>
                <th>Donor Partner</th>
                <td><strong>{{ $restaurant->restaurant_name ?? '-' }}</strong> (Owner: {{ $restaurant->owner_name ?? '-' }})</td>
            </tr>
            <tr>
                <th>Donor Address</th>
                <td>{{ $restaurant->address ?? '-' }}, {{ $restaurant->city ?? '' }}, {{ $restaurant->state ?? '' }}</td>
            </tr>
            <tr>
                <th>Recipient NGO</th>
                <td><strong>{{ $ngo->ngo_name ?? '-' }}</strong> (Reg. No: {{ $ngo->registration_number ?? '-' }})</td>
            </tr>
            <tr>
                <th>Food Item Donated</th>
                <td><strong>{{ $donation->food_name ?? '-' }}</strong> ({{ $donation->category ?? 'General' }} — {{ $donation->food_type ?? '' }})</td>
            </tr>
            <tr>
                <th>Quantity Rescued</th>
                <td><strong>{{ $donation->quantity ?? '-' }} {{ $donation->unit ?? '' }}</strong> (Approx. {{ $certificate->meals_count }} meals distributed)</td>
            </tr>
            <tr>
                <th>Handover Verification</th>
                <td>
                    <span class="badge-verified">✓ Verified via {{ strtoupper($verification->verification_method ?? 'OTP') }}</span>
                    @if($verification?->verified_at)
                        on {{ $verification->verified_at->format('M d, Y h:i A') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>Handover Location</th>
                <td>{{ $donation->pickup_address ?? '-' }}</td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td style="width: 50%;">
                    <div class="qr-section">
                        @if(!empty($qrCodeDataUri))
                            <img src="{{ $qrCodeDataUri }}" alt="Verification QR Code">
                        @endif
                        <small>Scan QR code or visit:<br>{{ $verifyUrl }}</small>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="signature-block">
                        <div class="signature-line">
                            <strong>{{ $ngo->owner_name ?? 'Authorized Representative' }}</strong><br>
                            {{ $ngo->ngo_name ?? 'Partner NGO' }}<br>
                            <small style="color: #64748b;">Digitally Confirmed Handover</small>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="disclaimer">
            This document is a formal Donation Acknowledgement Certificate issued by the Food Waste Rescue platform. It records verifiable surplus food rescue transactions between registered food donors and verified NGOs. Unless officially specified by governing authorities, this document does not constitute an official statutory tax exemption claim.
        </div>
    </div>
</body>
</html>
