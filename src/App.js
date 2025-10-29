import apiFetch from "@wordpress/api-fetch";

const useState = wp.element.useState;
const useEffect = wp.element.useEffect;

const updateOptions = async (options) => {
	await apiFetch({
		method: "POST",
		path: "/cords/v1/options",
		data: options,
	});
};

const updatePage = async ({ id, meta }) => {
	await apiFetch({
		method: "POST",
		path: `/wp/v2/pages/${id}`,
		data: {
			meta,
		},
	});
};

const App = () => {
	const [pages, setPages] = useState(null);
	const [options, setOptions] = useState({
		api_key: "",
	});
	const [load, setLoad] = useState(true);
	const [currentPage, setCurrentPage] = useState(1);
	const [totalPages, setTotalPages] = useState(1);
	const perPage = 10; // show 10 items per page

	useEffect(() => {
		const fetchData = async () => {
			// Fetch pages with pagination
			const response = await apiFetch({
				path: `/wp/v2/pages?page=${currentPage}&per_page=${perPage}`,
				parse: false, // we need headers
			});

			const data = await response.json();

			// Get total pages from response headers
			const total = parseInt(response.headers.get("X-WP-TotalPages"), 10);
			setTotalPages(total);

			setPages(data);

			// Fetch your options as before
			const opts = await apiFetch({ path: "/cords/v1/options" });
			setOptions(opts);

			setLoad(false);
		};

		fetchData();
	}, [load]);

	const nextPage = () => {
		if (currentPage < totalPages) {
			setCurrentPage(currentPage + 1);
			setLoad(true);
		}
	};

	const prevPage = () => {
		if (currentPage > 1) {
			setCurrentPage(currentPage - 1);
			setLoad(true);
		}
	};

	return (
		<div>
			<h1>CORDS</h1>
			<p>Welcome to the CORDS admin dashboard.</p>
			<hr />
			<h3>Pages</h3>
			<p>Select which pages you would like to enable CORDS and/or the widget on.</p>
			<table className="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<th>Title</th>
						<th>CORDS Widget</th>
					</tr>
				</thead>
				<tbody>
					{pages !== null &&
						pages.map((page) => (
							<tr key={page.id}>
								<td>
									{page.title.rendered.length ? page.title.rendered : "No Title"}
								</td>
								{/* <td>
									<select
										name="enabled"
										value={page.meta.cords_enabled ? "true" : "false"}
										onChange={(e) => {
											updatePage({
												id: page.id,
												meta: {
													...page.meta,
													cords_enabled: e.target.value === "true",
												},
											}).then(() => {
												setLoad(true);
											});
										}}
									>
										<option value="true">True</option>
										<option value="false">False</option>
									</select>
								</td> */}
								<td>
									<select
										name="enabled"
										value={page.meta.cords_widget ? "true" : "false"}
										onChange={(e) => {
											updatePage({
												id: page.id,
												meta: {
													...page.meta,
													cords_widget: e.target.value === "true",
												},
											}).then(() => {
												setLoad(true);
											});
										}}
									>
										<option value="true">Show</option>
										<option value="false">Hide</option>
									</select>
								</td>
							</tr>
						))}
				</tbody>
			</table>
			{/* Pagination Controls */}
			<div style={{ marginTop: "1rem", display: "flex", alignItems: "center", gap: "1rem" }}>
				<button
					onClick={prevPage}
					disabled={currentPage === 1}
					className="button"
				>
					← Previous
				</button>
				<span>
					Page {currentPage} of {totalPages}
				</span>
				<button
					onClick={nextPage}
					disabled={currentPage === totalPages}
					className="button"
				>
					Next →
				</button>
			</div>
			<h3>API Key</h3>
			<p>
				Enter your CORDS API key below. This can be found at{" "}
				<a href="https://partners.cords.ai">https://partners.cords.ai</a>
			</p>
			<input
				type="text"
				value={options.api_key}
				onChange={(e) => {
					setOptions({
						api_key: e.target.value,
					});
				}}
			/>
			<button
				onClick={() => {
					updateOptions(options);
				}}
			>
				Save
			</button>
		</div>
	);
};

export default App;
