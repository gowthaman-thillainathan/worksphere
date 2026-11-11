import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { DataTable, type DataTableColumn } from './DataTable';

interface Row {
  id: string;
  name: string;
}

const columns: DataTableColumn<Row, 'name'>[] = [{ id: 'name', header: 'Name', sortField: 'name', cell: (row) => row.name }];

describe('DataTable', () => {
  it('exposes the sort state to assistive technology and toggles direction', async () => {
    const onSortChange = vi.fn();
    render(
      <DataTable caption="People" columns={columns} rows={[{ id: '1', name: 'Ada' }]} getRowId={(row) => row.id} sort={{ field: 'name', direction: 'asc' }} onSortChange={onSortChange} empty="Empty" />,
    );

    expect(screen.getByRole('columnheader', { name: /name/i })).toHaveAttribute('aria-sort', 'ascending');

    await userEvent.click(screen.getByRole('button', { name: /name/i }));

    expect(onSortChange).toHaveBeenCalledWith({ field: 'name', direction: 'desc' });
  });

  it('shows the empty state when there are no rows', () => {
    render(<DataTable caption="People" columns={columns} rows={[]} getRowId={(row) => row.id} empty="Nobody here" />);

    expect(screen.getByText('Nobody here')).toBeInTheDocument();
  });

  it('marks the table busy while loading', () => {
    render(<DataTable caption="People" columns={columns} rows={undefined} getRowId={(row) => row.id} isLoading empty="Empty" />);

    expect(screen.getByRole('table').parentElement).toHaveAttribute('aria-busy', 'true');
  });
});
