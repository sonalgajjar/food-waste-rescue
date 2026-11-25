<?php

namespace App\Exports;

use App\Models\FoodDonation;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DonationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Enumerable
    {
        return FoodDonation::with('restaurant')->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Food Name',
            'Category',
            'Type',
            'Quantity',
            'Unit',
            'Restaurant',
            'Status',
            'Cooked Time',
            'Expiry Time'
        ];
    }

    public function map(mixed $donation): array
    {
        return [
            $donation->id,
            $donation->food_name,
            $donation->category,
            $donation->food_type,
            $donation->quantity,
            $donation->unit,
            $donation->restaurant->restaurant_name ?? 'N/A',
            $donation->status,
            $donation->cooked_time ? $donation->cooked_time->format('Y-m-d H:i') : '',
            $donation->expiry_time ? $donation->expiry_time->format('Y-m-d H:i') : '',
        ];
    }
}
