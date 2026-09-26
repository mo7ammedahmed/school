import {
    createSortedRowModel,
    rowSortingFeature,
    sortFns,
    tableFeatures,
    type ColumnDef as CoreColumnDef,
    type RowData,
} from '@tanstack/react-table';

/**
 * The single table feature set every DataTable in the app registers.
 *
 * TanStack Table v9 makes features explicit and tree-shakeable, so the features
 * are declared here once and threaded through `useTable` and every column
 * definition. Adding a capability (filtering, pagination, row selection) means
 * adding it here, not in each page.
 */
export const tableFeatureSet = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns,
});

/**
 * Column definition with the app's feature set pre-bound.
 *
 * v9's `ColumnDef` takes the feature set as its first type argument, which makes
 * the common case (`ColumnDef<Row, Value>`) awkward at every call site. This
 * keeps the data type first, as v8 did.
 */
export type ColumnDef<TData extends RowData = any, TValue = any> = CoreColumnDef<
    typeof tableFeatureSet,
    TData,
    TValue
>;
