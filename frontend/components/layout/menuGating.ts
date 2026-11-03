import type { MenuItemType } from "@/types/layout";

/**
 * Gating menu sidebar berdasarkan permission (pure function, tanpa side effect).
 *
 * Aturan:
 * - node tanpa `permission` (termasuk judul section, tanpa aksi) selalu tampil.
 * - node ber-permission tampil bila user null (fallback), memiliki permission
 *   tersebut, atau ber-role `super_admin`.
 * - node dengan children hanya tampil bila minimal satu children tampil.
 * - title section (isTitle) hanya dipertahankan bila masih ada item
 *   tampil di sisi kanannya (section yang sama).
 */
export type GatingUser = {
  permissions: string[];
  roles: string[];
} | null;

const nodeIsVisible = (
  item: MenuItemType,
  user: GatingUser,
): boolean => {
  if (!item.permission) return true;
  if (!user) return true; // defensif: sesi belum termuat → jangan menyembunyikan
  return (
    user.permissions.includes(item.permission) ||
    user.roles.includes("super_admin")
  );
};

const filterNode = (
  item: MenuItemType,
  user: GatingUser,
): MenuItemType | null => {
  const visible = nodeIsVisible(item, user);
  if (!visible) return null;

  if (item.children != null) {
    const children = filterMenuByPermission(item.children, user);
    if (children.length === 0) return null;
    return { ...item, children };
  }
  return item;
};

const pruneSectionTitles = (
  items: MenuItemType[],
): MenuItemType[] => {
  const result: MenuItemType[] = [];
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (!item.isTitle) {
      result.push(item);
      continue;
    }
    // Title dipertahankan hanya bila masih ada item tampil di section-nya
    // (item non-title pertama sebelum title section berikutnya).
    let hasVisibleItemInSection = false;
    for (const next of items.slice(i + 1)) {
      if (next.isTitle) break;
      hasVisibleItemInSection = true;
      break;
    }
    if (hasVisibleItemInSection) result.push(item);
  }
  return result;
};

export function filterMenuByPermission(
  items: MenuItemType[],
  user: GatingUser,
): MenuItemType[] {
  const filtered = items
    .map((item) => filterNode(item, user))
    .filter((item): item is MenuItemType => item !== null);
  return pruneSectionTitles(filtered);
}
