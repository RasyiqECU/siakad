import dayjs from "dayjs";
import "dayjs/locale/id";

dayjs.locale("id");

/** Format tanggal Indonesia: DD MMM YYYY (mis. 02 Nov 2026). */
export function formatDate(value: string | Date | null | undefined): string {
  if (!value) return "-";
  return dayjs(value).format("DD MMM YYYY");
}

/** Format tanggal-jam Indonesia: DD MMM YYYY HH:mm (mis. 02 Nov 2026 14:30). */
export function formatDateTime(
  value: string | Date | null | undefined,
): string {
  if (!value) return "-";
  return dayjs(value).format("DD MMM YYYY HH:mm");
}
