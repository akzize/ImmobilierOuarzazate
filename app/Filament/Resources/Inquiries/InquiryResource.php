<?php

namespace App\Filament\Resources\Inquiries;

use App\Enums\InquiryStatus;
use App\Enums\NavigationGroup;
use App\Filament\Resources\Inquiries\Pages\ManageInquiries;
use App\Filament\Resources\Inquiries\Schemas\InquiryForm;
use App\Filament\Resources\Inquiries\Tables\InquiriesTable;
use App\Models\Inquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Leads;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return __('inquiry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inquiries');
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'phone', 'email'];
    }

    /**
     * Number of inquiries nobody has answered yet.
     */
    public static function getNavigationBadge(): ?string
    {
        $newCount = Inquiry::query()->where('status', InquiryStatus::New)->count();

        return $newCount > 0 ? (string) $newCount : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return InquiryStatus::New->getColor();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('New inquiries');
    }

    public static function form(Schema $schema): Schema
    {
        return InquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInquiries::route('/'),
        ];
    }
}
