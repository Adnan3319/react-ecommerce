import { Head } from "@inertiajs/react";

const PageMeta = ({
  title,
  description,
}: {
  title: string;
  description: string;
}) => (
  <Head title={title}>
    <meta name="description" content={description} />
  </Head>
);

export default PageMeta;
