<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\LegalTitle;
use App\Enums\PropertyGroup;
use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Filament\Forms\Components\MapPicker;
use App\Filament\Resources\Owners\Schemas\OwnerForm;
use App\Models\PropertyImage;
use App\Models\PropertyType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Two-step wizard: listing details, then photos & publishing.
 * The create page walks through the steps in order (CreateProperty::getSteps()); the edit page can jump between them.
 * Type-specific fields are stored in the `details` JSON column (see docs/architecture.md, A.3).
 */
class PropertyForm
{
    /**
     * Bulk photo drop zone. Not a column: the create and edit pages turn its files into images after saving.
     */
    public const string NEW_PHOTOS_FIELD = 'new_photos';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make(self::steps())
                    ->skippable(),
            ]);
    }

    /**
     * @return array<Step>
     */
    public static function steps(): array
    {
        return [
            Step::make(__('Listing details'))
                ->description(__('Type, texts, price and characteristics'))
                ->icon('heroicon-o-home-modern')
                ->schema([
                    self::basicsSection(),
                    self::contentSection(),
                    self::priceSection(),
                    self::builtSection(),
                    self::landSection(),
                    self::commercialSection(),
                    self::amenitiesSection(),
                    self::mapAndMediaSection(),
                ]),
            Step::make(__('Photos & publishing'))
                ->description(__('Gallery, status and private information'))
                ->icon('heroicon-o-photo')
                ->schema([
                    self::photosSection(),
                    self::publishingSection(),
                    self::privateSection(),
                ]),
        ];
    }

    private static function basicsSection(): Section
    {
        return Section::make(__('Listing basics'))
            ->columns(3)
            ->schema([
                Select::make('property_type_id')
                    ->label(__('Property type'))
                    ->relationship('propertyType', 'label_en', fn (Builder $query) => $query->orderBy('position'))
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('transaction')
                    ->options(TransactionType::class)
                    ->required()
                    ->live(),
                Select::make('city_id')
                    ->label(__('City'))
                    ->relationship('city', 'name_en')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    private static function publishingSection(): Section
    {
        return Section::make(__('Publishing'))
            ->columns(2)
            ->schema([
                Select::make('status')
                    ->options(PropertyStatus::class)
                    ->default(PropertyStatus::Draft)
                    ->required()
                    ->live(),
                Toggle::make('featured')
                    ->inline(false)
                    ->helperText(__('Shown first on the home page.')),
                TextEntry::make('reference')
                    ->hiddenOn('create'),
                TextEntry::make('slug')
                    ->label(__('URL slug'))
                    ->hiddenOn('create'),
                TextEntry::make('published_at')
                    ->label(__('First published'))
                    ->dateTime()
                    ->placeholder(__('Not published yet'))
                    ->hiddenOn('create'),
            ]);
    }

    private static function contentSection(): Section
    {
        return Section::make(__('Title & description'))
            ->description(__('Arabic is the main language of the site. English is optional: when it is blank, visitors see the Arabic text.'))
            ->schema([
                Tabs::make()
                    ->tabs([
                        Tab::make(__('Arabic'))
                            ->badge(__('Required'))
                            ->badgeColor('primary')
                            ->schema([
                                TextInput::make('title_ar')
                                    ->label(__('Title (Arabic)'))
                                    ->required()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                                RichEditor::make('description_ar')
                                    ->label(__('Description (Arabic)'))
                                    ->required()
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                        Tab::make(__('English'))
                            ->badge(__('Optional'))
                            ->badgeColor('gray')
                            ->schema([
                                TextInput::make('title_en')
                                    ->label(__('Title (English)'))
                                    ->maxLength(255)
                                    ->helperText(__('The first English title also becomes the URL slug.')),
                                RichEditor::make('description_en')
                                    ->label(__('Description (English)')),
                            ]),
                    ]),
            ]);
    }

    private static function photosSection(): Section
    {
        return Section::make(__('Photos'))
            ->description(__('Drag to reorder. At least one photo is required to publish. If no photo is marked as cover, the first one is used.'))
            ->icon('heroicon-o-photo')
            ->schema([
                self::photoUpload(self::NEW_PHOTOS_FIELD)
                    ->label(__('Add several photos at once'))
                    ->helperText(__('Drop all the photos here: they are added after the existing ones, in this order, when you save.'))
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->maxFiles(40)
                    ->panelLayout('grid'),
                Repeater::make('images')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('position')
                    ->minItems(fn (Get $get): int => self::status($get) === PropertyStatus::Published && blank($get(self::NEW_PHOTOS_FIELD)) ? 1 : 0)
                    ->defaultItems(0)
                    ->addActionLabel(__('Add photo'))
                    ->itemLabel(fn (array $state): ?string => ($state['is_cover'] ?? false) ? __('Cover photo') : null)
                    ->grid(['default' => 1, 'sm' => 2, 'xl' => 3])
                    ->schema([
                        self::photoUpload('path')
                            ->hiddenLabel()
                            ->required(),
                        TextInput::make('alt_text')
                            ->hiddenLabel()
                            ->placeholder(__('Describe the photo (alt text)'))
                            ->maxLength(255),
                        Toggle::make('is_cover')
                            ->label(__('Use as cover'))
                            ->live(),
                    ]),
            ]);
    }

    /**
     * Same storage and resizing for single photos and bulk uploads.
     */
    private static function photoUpload(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk(PropertyImage::DISK)
            ->directory(PropertyImage::DIRECTORY)
            ->visibility('public')
            ->maxSize(10240)
            ->automaticallyResizeImagesMode('contain')
            ->automaticallyResizeImagesToWidth('1920')
            ->automaticallyResizeImagesToHeight('1920');
    }

    private static function priceSection(): Section
    {
        return Section::make(__('Price & surface'))
            ->columns(3)
            ->schema([
                TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('MAD')
                    ->suffix(fn (Get $get): ?string => match (self::transaction($get)) {
                        TransactionType::Rent => __('/ month'),
                        TransactionType::SeasonalRent => __('/ night'),
                        default => null,
                    }),
                TextInput::make('surface_m2')
                    ->label(__('Surface'))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->suffix('m²'),
                Select::make('legal_title')
                    ->label(__('Legal title'))
                    ->options(LegalTitle::class),
                Toggle::make('price_negotiable')
                    ->label(__('Price negotiable'))
                    ->columnSpanFull(),
                TextInput::make('details.deposit')
                    ->label(__('Deposit'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('MAD')
                    ->visible(fn (Get $get): bool => in_array(self::transaction($get), [TransactionType::Rent, TransactionType::SeasonalRent], true)),
                TextInput::make('details.monthly_charges')
                    ->label(__('Monthly charges (syndic)'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('MAD')
                    ->visible(fn (Get $get): bool => self::group($get) === PropertyGroup::Built),
                TextInput::make('details.goodwill')
                    ->label(__('Goodwill / Pas-de-porte'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('MAD')
                    ->visible(fn (Get $get): bool => self::group($get) === PropertyGroup::Commercial),
            ]);
    }

    private static function builtSection(): Section
    {
        return Section::make(__('Building details'))
            ->visible(fn (Get $get): bool => self::group($get) === PropertyGroup::Built)
            ->schema([
                Fieldset::make(__('Rooms & spaces'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('built_area_m2')->label(__('Built area'))->numeric()->minValue(0)->suffix('m²'),
                        TextInput::make('bedrooms')->integer()->minValue(0)->maxValue(255),
                        TextInput::make('bathrooms')->integer()->minValue(0)->maxValue(255),
                        TextInput::make('details.living_rooms')->label(__('Living rooms / Salons'))->integer()->minValue(0),
                        TextInput::make('details.kitchens')->label(__('Kitchens'))->integer()->minValue(0),
                        TextInput::make('details.parking')->label(__('Parking / Garage spaces'))->integer()->minValue(0),
                    ]),
                Fieldset::make(__('Condition & building'))
                    ->columns(3)
                    ->schema([
                        Select::make('details.condition')->label(__('Condition'))->options([
                            'new' => __('New'),
                            'good' => __('Good'),
                            'to_renovate' => __('To renovate'),
                            'under_construction' => __('Under construction'),
                        ]),
                        Select::make('details.furnished')->label(__('Furnished'))->options([
                            'yes' => __('Yes'),
                            'semi' => __('Semi-furnished'),
                            'no' => __('No'),
                        ]),
                        Select::make('details.view')->label(__('Orientation / View'))->options([
                            'mountain' => __('Mountain'),
                            'street' => __('Street'),
                            'garden' => __('Garden'),
                            'palm_grove' => __('Palm grove'),
                            'kasbah' => __('Kasbah'),
                        ]),
                        TextInput::make('details.year_built')->label(__('Year built'))->integer()->minValue(1800)->maxValue((int) date('Y') + 5),
                        TextInput::make('details.floor')->label(__('Floor'))->integer(),
                        TextInput::make('details.total_floors')->label(__('Total floors'))->integer()->minValue(0),
                    ]),
                Fieldset::make(__('Riad / Kasbah'))
                    ->visible(fn (Get $get): bool => in_array(self::type($get)?->code, ['RIAD', 'KSB'], true))
                    ->columns(4)
                    ->schema([
                        TextInput::make('details.patios')->label(__('Number of patios'))->integer()->minValue(0),
                        Toggle::make('details.restored')->label(__('Restored'))->inline(false),
                        Toggle::make('details.guesthouse_ready')->label(__('Usable as guesthouse'))->inline(false),
                        TextInput::make('details.heritage_classification')->label(__('Heritage classification'))->maxLength(255),
                    ]),
            ]);
    }

    private static function landSection(): Section
    {
        return Section::make(__('Land details'))
            ->visible(fn (Get $get): bool => self::group($get) === PropertyGroup::Land)
            ->columns(3)
            ->schema([
                Select::make('details.zoning')->label(__('Land use / Zoning'))->options([
                    'residential' => __('Residential'),
                    'commercial' => __('Commercial'),
                    'agricultural' => __('Agricultural'),
                    'industrial' => __('Industrial'),
                    'mixed' => __('Mixed'),
                ]),
                TextInput::make('details.facade_width')->label(__('Facade width'))->numeric()->minValue(0)->suffix('m'),
                TextInput::make('details.buildable')->label(__('Buildable (max floors / COS)'))->placeholder('R+2')->maxLength(50),
                Toggle::make('details.fenced')->label(__('Fenced')),
                Toggle::make('details.road_access')->label(__('Road access')),
                CheckboxList::make('details.utilities')->label(__('Utilities'))->options([
                    'water' => __('Water'),
                    'electricity' => __('Electricity'),
                    'sewage' => __('Sewage'),
                ]),
                Fieldset::make(__('Farm'))
                    ->visible(fn (Get $get): bool => self::type($get)?->code === 'FARM')
                    ->schema([
                        Toggle::make('details.water_source')->label(__('Water source / well'))->inline(false),
                        TextInput::make('details.crops')->label(__('Trees / crops'))->maxLength(255),
                    ]),
            ]);
    }

    private static function commercialSection(): Section
    {
        return Section::make(__('Commercial details'))
            ->visible(fn (Get $get): bool => self::group($get) === PropertyGroup::Commercial)
            ->columns(3)
            ->schema([
                TextInput::make('details.activity_allowed')->label(__('Activity allowed'))->maxLength(255),
                TextInput::make('details.rooms_count')->label(__('Rooms / Offices'))->integer()->minValue(0),
                TextInput::make('details.parking')->label(__('Parking spaces'))->integer()->minValue(0),
                TextInput::make('details.street_frontage')->label(__('Street frontage'))->numeric()->minValue(0)->suffix('m'),
                TextInput::make('details.ceiling_height')->label(__('Ceiling height'))->numeric()->minValue(0)->suffix('m'),
                Fieldset::make(__('Hotel / guesthouse'))
                    ->visible(fn (Get $get): bool => self::type($get)?->code === 'HOT')
                    ->schema([
                        TextInput::make('details.hotel_rooms')->label(__('Rooms'))->integer()->minValue(0),
                        Select::make('details.stars')->label(__('Stars'))->options(array_combine(range(1, 5), range(1, 5))),
                        Toggle::make('details.licensed')->label(__('Licensed'))->inline(false),
                    ]),
            ]);
    }

    /**
     * Built properties and commercial units (hotels, offices, shops) both have amenities; land does not.
     */
    private static function amenitiesSection(): Section
    {
        return Section::make(__('Amenities'))
            ->visible(fn (Get $get): bool => in_array(self::group($get), [PropertyGroup::Built, PropertyGroup::Commercial], true))
            ->schema([
                CheckboxList::make('amenities')
                    ->hiddenLabel()
                    ->relationship('amenities', 'label_en')
                    ->columns(['default' => 2, 'md' => 4]),
            ]);
    }

    private static function mapAndMediaSection(): Section
    {
        return Section::make(__('Location & media'))
            ->description(__('Click the map to drop the pin, or use the polygon / rectangle tools to draw the outline of the plot. You can also type the coordinates.'))
            ->collapsible()
            ->columns(3)
            ->schema([
                MapPicker::make('boundary')
                    ->hiddenLabel()
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90),
                TextInput::make('longitude')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180),
                TextInput::make('video_url')
                    ->label(__('Video URL'))
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://youtube.com/...'),
            ]);
    }

    private static function privateSection(): Section
    {
        return Section::make(__('Private information'))
            ->description(__('Internal only. Never shown on the public website.'))
            ->icon('heroicon-o-lock-closed')
            ->columns(2)
            ->collapsed()
            ->schema([
                Select::make('owner_id')
                    ->label(__('Owner'))
                    ->relationship('owner', 'name_en')
                    ->searchable(['name_en', 'name_ar', 'phone'])
                    ->preload()
                    ->createOptionForm(fn (Schema $schema): Schema => OwnerForm::configure($schema)),
                TextInput::make('address')
                    ->maxLength(255),
                Textarea::make('internal_notes')
                    ->label(__('Internal notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    private static function type(Get $get): ?PropertyType
    {
        $typeId = $get('property_type_id');

        return filled($typeId) ? self::propertyTypes()->get((int) $typeId) : null;
    }

    private static function group(Get $get): ?PropertyGroup
    {
        return self::type($get)?->group;
    }

    private static function transaction(Get $get): ?TransactionType
    {
        $transaction = $get('transaction');

        return $transaction instanceof TransactionType ? $transaction : TransactionType::tryFrom((string) $transaction);
    }

    private static function status(Get $get): ?PropertyStatus
    {
        $status = $get('status');

        return $status instanceof PropertyStatus ? $status : PropertyStatus::tryFrom((string) $status);
    }

    /**
     * @return Collection<int, PropertyType>
     */
    private static function propertyTypes(): Collection
    {
        return once(fn (): Collection => PropertyType::all()->keyBy('id'));
    }
}
