"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { Pagination } from "react-bootstrap";

/**
 * Navigasi paginasi Bootstrap + ringkasan "Menampilkan X–Y dari Z".
 * Halaman panjang dipangkas: 1 … (c-1 c c+1) … terakhir.
 */
const PaginationBar = ({
  page,
  lastPage,
  total,
  perPage = 15,
  onPageChange,
}: {
  page: number;
  lastPage: number;
  total: number;
  perPage?: number;
  onPageChange: (page: number) => void;
}) => {
  const from = total === 0 ? 0 : (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);

  const pages: Array<number | "ellipsis-left" | "ellipsis-right"> = [];
  if (lastPage <= 7) {
    for (let p = 1; p <= lastPage; p++) pages.push(p);
  } else {
    pages.push(1);
    if (page > 3) pages.push("ellipsis-left");
    for (
      let p = Math.max(2, page - 1);
      p <= Math.min(lastPage - 1, page + 1);
      p++
    )
      pages.push(p);
    if (page < lastPage - 2) pages.push("ellipsis-right");
    pages.push(lastPage);
  }

  return (
    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
      <span className="text-muted fs-13">
        Menampilkan {from}–{to} dari {total}
      </span>
      {lastPage > 1 && (
        <Pagination className="mb-0">
          <Pagination.Prev
            disabled={page <= 1}
            onClick={() => onPageChange(page - 1)}
          >
            <IconifyIcon icon="ri:arrow-left-s-line" />
          </Pagination.Prev>
          {pages.map((p, idx) =>
            typeof p === "number" ? (
              <Pagination.Item
                key={p}
                active={p === page}
                onClick={() => p !== page && onPageChange(p)}
              >
                {p}
              </Pagination.Item>
            ) : (
              <Pagination.Ellipsis
                key={`${p}-${idx}`}
                disabled
              />
            ),
          )}
          <Pagination.Next
            disabled={page >= lastPage}
            onClick={() => onPageChange(page + 1)}
          >
            <IconifyIcon icon="ri:arrow-right-s-line" />
          </Pagination.Next>
        </Pagination>
      )}
    </div>
  );
};

export default PaginationBar;
