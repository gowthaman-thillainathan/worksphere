import { describe, expect, it } from 'vitest';
import { settleAll } from './settle-all';

describe('settleAll', () => {
  it('reports every success and failure without exceeding the concurrency', async () => {
    let running = 0;
    let peak = 0;

    const outcome = await settleAll(
      [1, 2, 3, 4, 5],
      async (value) => {
        running += 1;
        peak = Math.max(peak, running);
        await new Promise((resolve) => setTimeout(resolve, 1));
        running -= 1;

        if (value % 2 === 0) throw new Error(`even ${value}`);

        return value * 10;
      },
      2,
    );

    expect(peak).toBe(2);
    expect(outcome.succeeded.map(({ result }) => result).sort()).toEqual([10, 30, 50]);
    expect(outcome.failed.map(({ item }) => item).sort()).toEqual([2, 4]);
  });
});
