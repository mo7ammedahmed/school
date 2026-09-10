<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Section;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'name_ar',
    'name_en',
    'slug',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'timezone',
    'locale',
    'currency',
    'logo_path',
    'favicon_path',
    'primary_color',
    'secondary_color',
    'accent_color',
    'metadata',
])]
#[UseFactory(SchoolFactory::class)]
#[Appends([
    'name',
])]
class School extends Model
{
    use HasFactory, SoftDeletes;

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_$locale"} ?? $this->name_en ?? $this->name_ar ?? '';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function navigationLabels(): HasMany
    {
        return $this->hasMany(SchoolNavigationLabel::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the school's theme configuration.
     * If not set, returns the default theme configuration and saves it.
     */
    public function getThemeConfig(): array
    {
        $metadata = $this->metadata ?? [];

        if (! isset($metadata['theme'])) {
            $metadata['theme'] = $this->getDefaultThemeConfig();
            $this->metadata = $metadata;
            $this->save();
        }

        return $metadata['theme'];
    }

    /**
     * Set the school's theme configuration.
     */
    public function setThemeConfig(array $config): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['theme'] = $config;
        $this->metadata = $metadata;

        // Sync the brand colors to the individual columns for backward compatibility
        if (isset($config['colorPrimary'])) {
            $this->primary_color = $config['colorPrimary'];
        }
        if (isset($config['colorSecondary'])) {
            $this->secondary_color = $config['colorSecondary'];
        }
        if (isset($config['colorAccent'])) {
            $this->accent_color = $config['colorAccent'];
        }
        // Note: logo_path and favicon_path are not part of the theme config for now

        $this->save();
    }

    /**
     * Get the default theme configuration based on the current design system.
     */
    protected function getDefaultThemeConfig(): array
    {
        return [
            // Base
            'colorBackground' => '#faf9f5',
            'colorForeground' => '#1c1a16',
            'colorCard' => '#ffffff',
            'colorCardForeground' => '#1c1a16',
            'colorPopover' => '#ffffff',
            'colorPopoverForeground' => '#1c1a16',
            // Brand
            'colorPrimary' => '#0a5c42',
            'colorPrimaryForeground' => '#ffffff',
            'colorSecondary' => '#f2efe8',
            'colorSecondaryForeground' => '#29261f',
            'colorAccent' => '#efecdf',
            'colorAccentForeground' => '#29261f',
            // Muted
            'colorMuted' => '#f2efe8',
            'colorMutedForeground' => '#74705f',
            // Border
            'colorBorder' => '#e6e1d3',
            'colorInput' => '#ddd8c9',
            'colorRing' => '#0a5c42',
            // State
            'colorSuccess' => '#15803d',
            'colorSuccessForeground' => '#ffffff',
            'colorWarning' => '#b45309',
            'colorWarningForeground' => '#ffffff',
            'colorError' => '#b42318',
            'colorErrorForeground' => '#ffffff',
            'colorInfo' => '#0e7490',
            'colorInfoForeground' => '#ffffff',
            // Link
            'colorLink' => '#0a5c42',
            'colorLinkHover' => '#0c3f30',
            // Sidebar
            'colorSidebar' => '#ffffff',
            'colorSidebarForeground' => '#1c1a16',
            'colorSidebarPrimary' => '#0a5c42',
            'colorSidebarPrimaryForeground' => '#ffffff',
            'colorSidebarAccent' => '#f2efe8',
            'colorSidebarAccentForeground' => '#29261f',
            'colorSidebarBorder' => '#e6e1d3',
            'colorSidebarRing' => '#0a5c42',
            // Header
            'colorHeader' => '#ffffff',
            'colorHeaderForeground' => '#1c1a16',
            'colorHeaderBorder' => '#e6e1d3',
            // Footer
            'colorFooter' => '#06281e',
            'colorFooterForeground' => '#f4f5f2',
            'colorFooterBorder' => '#1c533e',
            // Table
            'colorTableHeader' => '#f2efe8',
            'colorTableHeaderForeground' => '#29261f',
            'colorTableRow' => '#ffffff',
            'colorTableRowHover' => '#faf9f5',
            'colorTableBorder' => '#e6e1d3',
            // Form
            'colorInputBackground' => '#ffffff',
            'colorInputForeground' => '#1c1a16',
            'colorInputPlaceholder' => '#74705f',
            'colorInputBorder' => '#ddd8c9',
            'colorInputFocus' => '#0a5c42',
            // Button
            'colorButtonPrimary' => '#0a5c42',
            'colorButtonPrimaryForeground' => '#ffffff',
            'colorButtonSecondary' => '#f2efe8',
            'colorButtonSecondaryForeground' => '#29261f',
            'colorButtonOutline' => '#ffffff',
            'colorButtonGhost' => 'transparent',
            // Typography (we'll use the same as the CSS variables for now)
            'fontSans' => '"Outfit", "IBM Plex Sans Arabic", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
            'fontSerif' => '"Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, Cambria, serif',
            'fontArabic' => '"IBM Plex Sans Arabic", "Outfit", ui-sans-serif, system-ui, sans-serif',
            'fontDisplay' => '"Fraunces", "Amiri", "Source Serif 4", ui-serif, Georgia, serif',
            'fontMono' => '"IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, monospace',
            // Radius
            'radiusSm' => '0.375rem',
            'radiusMd' => '0.5rem',
            'radiusLg' => '0.75rem',
            'radiusXl' => '1rem',
            'radius2xl' => '1.25rem',
            // Shadows
            'shadowSm' => '0 1px 2px 0 rgb(28 26 22 / 0.05)',
            'shadowMd' => '0 2px 8px -2px rgb(28 26 22 / 0.08), 0 4px 16px -6px rgb(28 26 22 / 0.06)',
            'shadowLg' => '0 10px 24px -8px rgb(28 26 22 / 0.12), 0 4px 8px -4px rgb(28 26 22 / 0.05)',
            'shadowPanel' => '0 1px 2px rgb(28 26 22 / 0.04), 0 24px 48px -24px rgb(28 26 22 / 0.18)',
            'shadowLift' => '0 1px 2px rgb(28 26 22 / 0.05), 0 18px 32px -14px rgb(6 40 30 / 0.22)',
        ];
    }
}
