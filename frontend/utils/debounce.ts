/** Debounce sederhana (pengganti lodash.debounce) untuk handler resize dsb. */
export const debounce = <TArgs extends unknown[]>(
  fn: (...args: TArgs) => void,
  waitMs = 300,
): ((...args: TArgs) => void) => {
  let timer: ReturnType<typeof setTimeout> | undefined;

  return (...args: TArgs) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => fn(...args), waitMs);
  };
};
