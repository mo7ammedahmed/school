import AppShell from '@/layouts/app-shell';
import { PageHeader } from '@/components/ui/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Select } from '@/components/ui/select';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2, Plus } from 'lucide-react';

interface GradingScale {
  id: number;
  name: string;
  description: string | null;
  scale: Array<{ grade: string; min: number; max: number }>;
  is_default: boolean;
}

interface GradingCategory {
  id: number;
  name: string;
  code: string | null;
  weight: number;
  description: string | null;
}

interface Props {
  school: { id: number; name: string };
  gradingScales: GradingScale[];
  gradingCategories: GradingCategory[];
  settings: {
    rounding_method: string;
    include_extracurricular: boolean;
  };
}

export default function OnboardingGradingSettings({ school, gradingScales, gradingCategories, settings }: Props) {
  return (
    <AppShell
      title="Grading Settings"
      breadcrumbs={[
        { label: 'Dashboard', href: '/dashboard' },
        { label: 'Settings', href: '/settings/general' },
        { label: 'Grading' },
      ]}
    >
      <PageHeader
        title="Grading Settings"
        description={`Manage grading scales and categories for ${school.name}`}
        actions={
          <Button variant="outline" asChild>
            <Link href="/settings/general">
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Link>
          </Button>
        }
      />

      <div className="space-y-6">
        <Card>
          <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-2">
              <CardTitle>Grading Scales</CardTitle>
              <CardDescription>
                Define the grading scales used to convert numerical scores to letter grades or grade points.
                Each scale consists of grade levels with minimum and maximum percentage ranges.
              </CardDescription>
            </div>
            <div className="flex items-center gap-3">
              <Button
                variant="outline"
              >
                <Plus className="mr-2 h-4 w-4" /> New Scale
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {gradingScales.length > 0 ? (
                gradingScales.map((scale) => (
                  <Card key={scale.id} className="border-l-4 border-l-blue-500">
                    <CardContent className="space-y-3">
                      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                          <h3 className="font-lg font-semibold flex items-center gap-2">
                            {scale.is_default ? (
                              <>
                                <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Default</span>
                                <span>{scale.name}</span>
                              </>
                            ) : (
                              <span>{scale.name}</span>
                            )}
                          </h3>
                          {scale.description && (
                            <p className="text-sm text-gray-500">{scale.description}</p>
                          )}
                        </div>
                        <div className="flex flex-col sm:flex-row sm:gap-4 sm:justify-end">
                          <div className="flex space-x-3">
                            <Button variant="outline" size="sm">
                              <Edit className="h-4 w-4" /> Edit
                            </Button>
                            <Button variant="outline" size="sm" className="text-red-500 hover:text-red-700">
                              <Trash2 className="h-4 w-4" /> Delete
                            </Button>
                            {!scale.is_default && (
                              <Button variant="outline" size="sm" className="text-green-600 hover:text-green-800">
                                Set as Default
                              </Button>
                            )}
                          </div>
                          <div className="mt-3 sm:mt-0 w-full sm:w-auto text-sm text-gray-500">
                            {scale.scale.map((grade, index) => (
                              <span key={index} className="inline-block px-2 py-1 mx-1 bg-gray-50 rounded text-sm">
                                {grade.grade}: {grade.min}% - {grade.max}%
                              </span>
                            ))}
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))
              ) : (
                <div className="text-center py-8 text-gray-500">
                  <p>No grading scales defined yet. Click &quot;New Scale&quot; to create one.</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div className="space-y-2">
              <CardTitle>Grading Categories</CardTitle>
              <CardDescription>
                Define how different types of assessments contribute to the final grade.
                Each category has a weight that determines its contribution to the overall grade.
              </CardDescription>
            </div>
            <div className="flex items-center gap-3">
              <Button
                variant="outline"
              >
                <Plus className="mr-2 h-4 w-4" /> New Category
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {gradingCategories.length > 0 ? (
                gradingCategories.map((category) => (
                  <Card key={category.id} className="border-l-4 border-l-green-500">
                    <CardContent className="space-y-3">
                      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                          <h3 className="font-lg font-semibold">{category.name}</h3>
                          {category.code && (
                            <span className="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                              {category.code}
                            </span>
                          )}
                          {category.description && (
                            <p className="text-sm text-gray-500">{category.description}</p>
                          )}
                        </div>
                        <div className="flex flex-col sm:flex-row sm:gap-4 sm:justify-end">
                          <div className="flex space-x-3">
                            <Button variant="outline" size="sm">
                              <Edit className="h-4 w-4" /> Edit
                            </Button>
                            <Button variant="outline" size="sm" className="text-red-500 hover:text-red-700">
                              <Trash2 className="h-4 w-4" /> Delete
                            </Button>
                          </div>
                          <div className="mt-3 sm:mt-0 w-full sm:w-auto text-sm text-gray-500">
                            Weight: {category.weight}%
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))
              ) : (
                <div className="text-center py-8 text-gray-500">
                  <p>No grading categories defined yet. Click &quot;New Category&quot; to create one.</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Basic Settings</CardTitle>
          </CardHeader>
          <CardContent>
            <form method="POST" action="/settings/grading" className="space-y-4">
              <div className="grid gap-6 md:grid-cols-2">
                <div>
                  <Label htmlFor="roundingMethod">Rounding Method</Label>
                  <Select id="roundingMethod" name="rounding_method" defaultValue={settings.rounding_method}>
                    <option value="nearest">Nearest (Standard Rounding)</option>
                    <option value="floor">Round Down</option>
                    <option value="ceil">Round Up</option>
                  </Select>
                  <p className="text-sm text-gray-500 mt-1">
                    Determines how scores are rounded when converting between numerical and letter grades.
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <Checkbox id="includeExtracurricular" name="include_extracurricular" defaultChecked={settings.include_extracurricular} />
                  <div>
                    <Label htmlFor="includeExtracurricular">Include Extracurricular Activities in Grade Calculation</Label>
                    <p className="text-sm text-gray-500">
                      When enabled, extracurricular activity scores will be included in the final grade calculation.
                    </p>
                  </div>
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <Button type="submit">
                  Save Settings
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppShell>
  );
}
