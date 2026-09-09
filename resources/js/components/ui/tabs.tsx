import * as React from 'react';
import { cn } from '@/lib/utils';

interface TabsProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
  defaultIndex?: number;
  onIndexChange?: (index: number) => void;
  children: React.ReactNode;
}

interface TabListProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
  children: React.ReactNode;
}

interface TabProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  className?: string;
  children: React.ReactNode;
}

interface TabPanelsProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
  children: React.ReactNode;
}

interface TabPanelProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
  children: React.ReactNode;
}

function Tabs({
  className,
  defaultIndex = 0,
  onIndexChange,
  children,
}: TabsProps) {
  const [index, setIndex] = React.useState(defaultIndex);

  const childrenArray = React.Children.toArray(children);

  const tabListEl = childrenArray.find(
    (child) => child.type === TabList
  );

  const tabPanelsEl = childrenArray.find(
    (child) => child.type === TabPanels
  );

  const handleSelect = (selectedIndex: number) => {
    setIndex(selectedIndex);
    onIndexChange?.(selectedIndex);
  };

  return (
    <div className={cn('space-y-4', className)}>
      {React.cloneElement(tabListEl as React.ReactElement, {
        onSelect: handleSelect,
        selectedIndex: index,
      })}
      {React.cloneElement(tabPanelsEl as React.ReactElement, {
        selectedIndex: index,
      })}
    </div>
  );
}

Tabs.displayName = 'Tabs';

function TabList({
  className,
  children,
  onSelect,
  selectedIndex,
}: TabListProps & {
  onSelect: (index: number) => void;
  selectedIndex: number;
}) {
  return (
    <div
      role="tablist"
      className={cn(
        'flex border-b border-border/80 bg-background px-2',
        className
      )}
    >
      {React.Children.map(children, (child, index) => {
        if (React.isValidElement(child)) {
          return React.cloneElement(child, {
            index,
            selected: index === selectedIndex,
            onSelect,
          });
        }
        return child;
      })}
    </div>
  );
}

TabList.displayName = 'Tabs.List';

function Tab({
  className,
  children,
  index,
  selected,
  onSelect,
}: TabProps & {
  index: number;
  selected: boolean;
  onSelect: (index: number) => void;
}) {
  return (
    <button
      role="tab"
      aria-selected={selected}
      aria-controls={`tab-panel-${index}`}
      id={`tab-${index}`}
      className={cn(
        'inline-flex items-center justify-center whitespace-nowrap rounded-t-md px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
        selected
          ? 'border-b-2 border-primary bg-primary/5 text-primary-foreground'
          : 'text-muted-foreground hover:text-foreground/80 hover:bg-muted/50',
        className
      )}
      onClick={() => onSelect(index)}
    >
      {children}
    </button>
  );
}

Tab.displayName = 'Tabs.Tab';

function TabPanels({
  className,
  children,
  selectedIndex,
}: TabPanelsProps & {
  selectedIndex: number;
}) {
  return (
    <div className={cn('space-y-6', className)}>
      {React.Children.map(children, (child, index) => {
        if (React.isValidElement(child) && index === selectedIndex) {
          return React.cloneElement(child, {
            index,
          });
        }
        return null;
      })}
    </div>
  );
}

TabPanels.displayName = 'Tabs.Panels';

function TabPanel({
  className,
  children,
  index,
}: TabPanelProps & {
  index: number;
}) {
  return (
    <div
      role="tabpanel"
      id={`tab-panel-${index}`}
      className={cn(
        'pb-4',
        className
      )}
    >
      {children}
    </div>
  );
}

TabPanel.displayName = 'Tabs.Panel';

export { Tabs, TabList, Tab, TabPanels, TabPanel };