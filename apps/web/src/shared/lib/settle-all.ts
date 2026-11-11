export interface Settled<Item, Result> {
  succeeded: { item: Item; result: Result }[];
  failed: { item: Item; error: unknown }[];
}

/**
 * Runs `task` for every item with at most `concurrency` in flight and
 * reports each outcome, so one failure does not hide the others.
 */
export async function settleAll<Item, Result>(items: readonly Item[], task: (item: Item) => Promise<Result>, concurrency: number): Promise<Settled<Item, Result>> {
  const outcome: Settled<Item, Result> = { succeeded: [], failed: [] };
  let next = 0;

  const worker = async () => {
    while (next < items.length) {
      const item = items[next++] as Item;

      try {
        outcome.succeeded.push({ item, result: await task(item) });
      } catch (error) {
        outcome.failed.push({ item, error });
      }
    }
  };

  await Promise.all(Array.from({ length: Math.min(concurrency, items.length) }, worker));

  return outcome;
}
