import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
	PanelBody,
	SelectControl,
	Spinner,
	Notice,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

export default function Edit({ attributes, setAttributes }) {
	const { centreId, centreData } = attributes;
	const [centres, setCentres] = useState([]);
	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState(null);
	const [debugInfo, setDebugInfo] = useState("");

	// Debug function
	const logDebug = (message, data = null) => {
		console.log(`[Awanui Debug] ${message}`, data);
		setDebugInfo(
			(prev) =>
				prev + `\n${message}` + (data ? `: ${JSON.stringify(data)}` : "")
		);
	};

	useEffect(() => {
		logDebug("Edit component mounted");
		logDebug("WordPress API URL", window.wpApiSettings?.root || "Not found");
	}, []);

	// Fetch all centres 
	useEffect(() => {
		logDebug("Fetching centres list");
		setIsLoading(true);
		setError(null);

		// Test basic WordPress API first
		apiFetch({ path: "/wp/v2/users/me" })
			.then(() => {
				logDebug("WordPress REST API is working");
				return apiFetch({ path: "/awanui/v1/centres" });
			})
			.catch(() => {
				logDebug("WordPress REST API test failed, trying direct API call");
				return apiFetch({ path: "/awanui/v1/centres" });
			})
			.then((data) => {
				logDebug("Centres data received", data);
				setCentres(data);
				setIsLoading(false);
			})
			.catch((err) => {
				logDebug("API call failed", err);
				console.error("API Error:", err);

				// Provide more specific error messages
				let errorMessage = "Failed to load centres.";
				if (err.code === "rest_no_route") {
					errorMessage =
						"API endpoint not found. Make sure the plugin is properly activated.";
				} else if (err.code === "rest_forbidden") {
					errorMessage = "Access denied to API endpoint.";
				} else if (err.message) {
					errorMessage = `API Error: ${err.message}`;
				}

				setError(errorMessage);
				setIsLoading(false);

				// Fallback: Set some test data for development
				if (process.env.NODE_ENV === "development") {
					logDebug("Using fallback test data");
					setCentres([
						{ name: "Test Centre 1", slug: "test-1" },
						{ name: "Test Centre 2", slug: "test-2" },
					]);
				}
			});
	}, []);

	// Fetch centre details when selected
	useEffect(() => {
		if (centreId) {
			logDebug("Fetching centre details", centreId);
			setIsLoading(true);
			setError(null);

			apiFetch({ path: `/awanui/v1/centre/${centreId}` })
				.then((data) => {
					logDebug("Centre data received", data);
					console.log("Saving centre data:", data);
					setAttributes({ centreData: data });
					setIsLoading(false);
				})
				.catch((err) => {
					logDebug("Centre details API call failed", err);
					console.error("Centre Details API Error:", err);

					let errorMessage = "Failed to load centre details.";
					if (err.message) {
						errorMessage = `API Error: ${err.message}`;
					}

					setError(errorMessage);
					setIsLoading(false);
				});
		}
	}, [centreId, setAttributes]);

	const blockProps = useBlockProps({
		className: "awanui-collection-centre-block",
	});

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title="Collection Centre Settings">
					<SelectControl
						label="Select a Centre"
						value={centreId}
						options={[
							{ label: "-- Select a Centre --", value: "" },
							...centres.map((centre) => ({
								label: centre.name,
								value: centre.slug,
							})),
						]}
						onChange={(value) => setAttributes({ centreId: value })}
					/>
					
					{process.env.NODE_ENV === "development" && (
						<details style={{ marginTop: "10px", fontSize: "12px" }}>
							<summary>Debug Info</summary>
							<pre style={{ whiteSpace: "pre-wrap", fontSize: "10px" }}>
								{debugInfo}
							</pre>
						</details>
					)}
				</PanelBody>
			</InspectorControls>

			{error && (
				<Notice status="error" isDismissible={false}>
					<p>{error}</p>
					<p>
						<small>Check the browser console for more details.</small>
					</p>
				</Notice>
			)}

			{isLoading && (
				<div style={{ textAlign: "center", padding: "20px" }}>
					<Spinner />
					<p>Loading...</p>
				</div>
			)}

			{centreData && !isLoading && (
				<div className="awanui-collection-centre">
					<h3>{centreData.name}</h3>
					<div className="address">
						<p>{centreData.address}</p>
						<p>{centreData.city}</p>
					</div>
					{centreData.phone && (
						<p className="phone">
							<a href={`tel:${centreData.phone}`}>{centreData.phone}</a>
						</p>
					)}
					<div className="opening-hours">
						<h4>Opening Hours</h4>
						<ul>
							{centreData.hours &&
								centreData.hours.map((day, index) => (
									<li key={index}>
										<span className="day">{day.day}:</span>
										<span className="hours">{day.hours}</span>
									</li>
								))}
						</ul>
					</div>
					{centreData.map_link && (
						<a
							href={centreData.map_link}
							target="_blank"
							rel="noopener noreferrer"
							className="directions-link"
						>
							Get Directions
						</a>
					)}
				</div>
			)}

			{!centreId && !isLoading && !error && (
				<div
					className="awanui-collection-centre-placeholder"
					style={{
						padding: "20px",
						textAlign: "center",
						background: "#f8f8f8",
						border: "1px dashed #ccc",
						borderRadius: "4px",
					}}
				>
					<p>Please select a collection centre from the block settings.</p>
					{centres.length === 0 && !isLoading && (
						<p>
							<small>No centres available. Check if the API is working.</small>
						</p>
					)}
				</div>
			)}
		</div>
	);
}
