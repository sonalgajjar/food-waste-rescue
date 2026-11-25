<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food Waste Rescue — Master System Report</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #059669; }
        .header p { margin: 5px 0 0; color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #059669; color: white; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>FOOD WASTE RESCUE — MASTER REPORT</h2>
        <p>Generated on {{ date('F d, Y — H:i:s') }} | Academic & Portfolio Submission Document</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Food Item</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Donor Restaurant</th>
                <th>Status</th>
                <th>Cooked Date</th>
                <th>Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $index => $d)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $d->food_name }}</strong></td>
                    <td>{{ $d->category }}</td>
                    <td>{{ $d->quantity }} {{ $d->unit }}</td>
                    <td>{{ $d->restaurant->restaurant_name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($d->status) }}</td>
                    <td>{{ $d->cooked_time ? $d->cooked_time->format('Y-m-d H:i') : '-' }}</td>
                    <td>{{ $d->expiry_time ? $d->expiry_time->format('Y-m-d H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Food Waste Rescue Platform &copy; {{ date('Y') }}. All rights reserved.</p>
    </div>
</body>
</html>
