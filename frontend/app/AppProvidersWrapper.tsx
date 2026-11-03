"use client";
import { NotificationProvider } from "@/context/useNotificationContext";
import { LayoutProvider } from "@/context/useLayoutContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import {ChildrenType} from "@/types/component-props";
import { useState } from "react";

/**
 * Provider global aplikasi:
 * - TanStack Query (server state)
 * - LayoutProvider (tema Adminto: sidebar, dark mode, dsb.)
 * - NotificationProvider (toast bootstrap)
 */
export default function AppProvidersWrapper({ children }: ChildrenType) {
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            retry: 1,
            refetchOnWindowFocus: false,
          },
        },
      }),
  );

  return (
    <QueryClientProvider client={queryClient}>
      <LayoutProvider>
        <NotificationProvider>
          {children}
          <ToastContainer theme="colored" />
        </NotificationProvider>
      </LayoutProvider>
    </QueryClientProvider>
  );
}
