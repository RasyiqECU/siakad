import PageUnderConstruction from "@/components/shared/PageUnderConstruction";

type Param = { module: string };

export default async function SingleModulePage({
  params,
}: {
  params: Promise<Param>;
}) {
  const { module } = await params;
  return <PageUnderConstruction moduleName={module} />;
}
