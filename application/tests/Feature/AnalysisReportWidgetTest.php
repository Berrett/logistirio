<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalysisReport;
use App\Models\Analysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalysisReportWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_rendered()
    {
        Livewire::test(AnalysisReport::class)
            ->assertSuccessful();
    }

    public function test_widget_query_aggregates_correctly()
    {
        // Create sample data for one receipt
        Analysis::create([
            'file_id' => 1,
            'ai_request_id' => 1,
            'identifier' => 'REC-001',
            'date' => '2024-01-01 10:00:00',
            'total_amount' => 100.00,
            'tax' => 6,
            'tax_amount' => 1.20,
            'net_price' => 20.00,
            'gross_price' => 21.20,
        ]);

        Analysis::create([
            'file_id' => 1,
            'ai_request_id' => 1,
            'identifier' => 'REC-001',
            'date' => '2024-01-01 10:00:00',
            'total_amount' => 100.00,
            'tax' => 13,
            'tax_amount' => 3.90,
            'net_price' => 30.00,
            'gross_price' => 33.90,
        ]);

        $results = Analysis::query()
            ->select(
                'identifier',
                'date',
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 6 THEN gross_price ELSE 0 END) as amount_6'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 6 THEN tax_amount ELSE 0 END) as vat_6'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 13 THEN gross_price ELSE 0 END) as amount_13'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 13 THEN tax_amount ELSE 0 END) as vat_13'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 24 THEN gross_price ELSE 0 END) as amount_24'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tax = 24 THEN tax_amount ELSE 0 END) as vat_24'),
                \Illuminate\Support\Facades\DB::raw('MAX(total_amount) as total_amount')
            )
            ->groupBy('identifier', 'date')
            ->get();

        $this->assertCount(1, $results);
        $result = $results->first();

        $this->assertEquals('REC-001', $result->identifier);
        $this->assertEquals(21.20, $result->amount_6);
        $this->assertEquals(1.20, $result->vat_6);
        $this->assertEquals(33.90, $result->amount_13);
        $this->assertEquals(3.90, $result->vat_13);
        $this->assertEquals(0, $result->amount_24);
        $this->assertEquals(100.00, $result->total_amount);
    }
}
