import { type BootstrapVariantType } from "./component-props";

export type NotificationContextType = {
  showNotification: ({
    title,
    message,
    variant,
  }: ShowNotificationType) => void;
};

export type ShowNotificationType = {
  title?: string;
  message: string;
  variant?: BootstrapVariantType;
  delay?: number;
};

export type ToastrProps = {
  show: boolean;
  onClose?: () => void;
} & ShowNotificationType;
