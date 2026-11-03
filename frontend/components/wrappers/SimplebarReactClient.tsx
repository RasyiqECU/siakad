"use client";
import type SimpleBarCore from "simplebar-core";
import SimpleBar, { type Props } from "simplebar-react";
import { ChildrenType } from "@/types/component-props";

type SimplebarReactClientProps = React.ForwardRefExoticComponent<
  Props & React.RefAttributes<SimpleBarCore | null>
>["arguments"] &
  ChildrenType;

const SimplebarReactClient = ({
  children,
  ...options
}: SimplebarReactClientProps) => {
  return <SimpleBar {...options}>{children}</SimpleBar>;
};

export default SimplebarReactClient;
