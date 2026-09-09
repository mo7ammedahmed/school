import * as React from 'react';
import { cn } from '@/lib/utils';

interface TabsContextValue {
    value: string;
    onSelect: (value: string) => void;
}

const TabsContext = React.createContext<TabsContextValue | null>(null);

function useTabsContext(): TabsContextValue {
    const ctx = React.useContext(TabsContext);
    if (!ctx) {
        throw new Error('Tabs compound components must be used inside <Tabs>');
    }
    return ctx;
}

interface TabsProps extends React.HTMLAttributes<HTMLDivElement> {
    defaultValue?: string;
    /** Controlled value — omit to let Tabs manage its own state. */
    value?: string;
    onValueChange?: (value: string) => void;
}

function Tabs({
    className,
    defaultValue = '',
    value,
    onValueChange,
    children,
    ...props
}: TabsProps) {
    const [internalValue, setInternalValue] = React.useState(defaultValue);
    const current = value ?? internalValue;

    const handleSelect = React.useCallback(
        (next: string) => {
            if (value === undefined) {
                setInternalValue(next);
            }
            onValueChange?.(next);
        },
        [value, onValueChange]
    );

    return (
        <TabsContext.Provider value={{ value: current, onSelect: handleSelect }}>
            <div className={cn('space-y-4', className)} {...props}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

Tabs.displayName = 'Tabs';

interface TabListProps extends React.HTMLAttributes<HTMLDivElement> {
    className?: string;
}

function TabList({ className, children, ...props }: TabListProps) {
    return (
        <div
            role="tablist"
            className={cn('flex border-b border-border/80 bg-background px-2', className)}
            {...props}
        >
            {children}
        </div>
    );
}

TabList.displayName = 'Tabs.List';

interface TabProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    value: string;
}

function Tab({ value, className, children, onClick, ...props }: TabProps) {
    const { value: selectedValue, onSelect } = useTabsContext();
    const selected = selectedValue === value;

    return (
        <button
            type="button"
            role="tab"
            data-state={selected ? 'active' : 'inactive'}
            aria-selected={selected}
            aria-controls={`tab-panel-${value}`}
            id={`tab-${value}`}
            onClick={(event) => {
                onSelect(value);
                onClick?.(event);
            }}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-t-md px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
                selected
                    ? 'border-b-2 border-primary bg-primary/5 text-primary-foreground'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground/80',
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

Tab.displayName = 'Tabs.Tab';

interface TabPanelsProps extends React.HTMLAttributes<HTMLDivElement> {
    className?: string;
}

function TabPanels({ className, children, ...props }: TabPanelsProps) {
    return (
        <div className={cn('space-y-6', className)} {...props}>
            {children}
        </div>
    );
}

TabPanels.displayName = 'Tabs.Panels';

interface TabPanelProps extends React.HTMLAttributes<HTMLDivElement> {
    value: string;
}

function TabPanel({ value, className, children, ...props }: TabPanelProps) {
    const { value: selectedValue } = useTabsContext();

    if (selectedValue !== value) {
        return null;
    }

    return (
        <div
            role="tabpanel"
            id={`tab-panel-${value}`}
            aria-labelledby={`tab-${value}`}
            className={cn('pb-4', className)}
            {...props}
        >
            {children}
        </div>
    );
}

TabPanel.displayName = 'Tabs.Panel';

export { Tabs, TabList, Tab, TabPanels, TabPanel };