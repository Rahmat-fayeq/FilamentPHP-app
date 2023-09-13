<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use App\Enums\ProductTypeEnum;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')->live(onBlur: true)->unique()->required()
                                    ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                        if ($operation != 'create') {
                                            return;
                                        }
                                        $set('slug', Str::slug($state));
                                    }),
                                TextInput::make('slug')->disabled()->dehydrated()->required()->unique(Product::class, 'slug', ignoreRecord: true),
                                MarkdownEditor::make('description')->columnSpanFull()
                            ])->columns(2),

                        Section::make('Pricing & Inventory')
                            ->schema([
                                TextInput::make('sku')->label('SKU (Stock Keeping Unit)')->unique()->required(),
                                TextInput::make('price')->numeric()->rules(['regex:/^\d{1,6}(\.\d{0,2})?/'])->required(),
                                TextInput::make('quantity')->numeric()->minValue(0)->maxValue(100)->required(),
                                Select::make('type')
                                    ->options([
                                        'downloadable' => ProductTypeEnum::DOWNLOADABLE->value,
                                        'deliverable' => ProductTypeEnum::DELIVERABLE->value
                                    ])->required()
                            ])->columns(2)

                    ]),
                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_visible')->label('Visibility')->helperText('Enable or disable product visibility')->default(true),
                                Toggle::make('is_featured')->label('Featured')->helperText('Enable or disable product featured'),
                                DatePicker::make('published_at')->label('Availability')->default(now()),
                            ]),

                        Section::make('Image')
                            ->schema([
                                FileUpload::make('image')->directory('form-attachments')->preserveFilenames()->image()->imageEditor()
                            ])->collapsible(),

                        Section::make('Associations')
                            ->schema([
                                Select::make('brand_id')
                                    ->relationship('brand', 'name')->required(),

                                Select::make('categories')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->required()
                            ])
                    ]),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('brand.name')->searchable()->sortable()->toggleable(),
                IconColumn::make('is_visible')->boolean()->label('Visibility')->sortable()->toggleable(),
                TextColumn::make('price')->sortable()->toggleable(),
                TextColumn::make('quantity')->sortable()->toggleable(),
                TextColumn::make('published_at')->sortable()->date(),
                TextColumn::make('type'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
