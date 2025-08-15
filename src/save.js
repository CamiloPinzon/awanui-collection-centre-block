import { useBlockProps } from "@wordpress/block-editor";

export default function Save({ attributes }) {
	const { centreData } = attributes;
	const blockProps = useBlockProps.save();

	// Debug: Log the complete data
	console.log("Rendering with:", centreData);

	if (!centreData) {
		return <div {...blockProps}>Loading data...</div>;
	}

	return (
		<div {...blockProps}>
			<h2
				style={{
					margin: "0 0 15px 0",
					color: "#2c3e50",
					fontSize: "1.5em",
				}}
			>
				{centreData.name}
			</h2>

			
			<div style={{ marginBottom: "15px" }}>
				<p style={{ margin: "4px 0" }}>{centreData.address}</p>
				<p style={{ margin: "4px 0" }}>{centreData.city}</p>
			</div>

			
			<p
				style={{
					margin: "0 0 20px 0",
					fontSize: "1.1em",
				}}
			>
				<a
					href={`tel:${centreData.phone.replace(/\D/g, "")}`}
					style={{ color: "#2980b9", textDecoration: "none" }}
				>
					{centreData.phone}
				</a>
			</p>

			
			<div style={{ marginBottom: "25px" }}>
				<h3
					style={{
						margin: "0 0 10px 0",
						fontSize: "1.2em",
						color: "#2c3e50",
					}}
				>
					Opening Hours
				</h3>
				<ul
					style={{
						listStyle: "none",
						padding: 0,
						margin: 0,
					}}
				>
					{centreData.hours.map((day, index) => (
						<li
							key={index}
							style={{
								display: "flex",
								justifyContent: "space-between",
								marginBottom: "6px",
								paddingBottom: "4px",
								borderBottom: "1px dashed #eee",
							}}
						>
							<span style={{ fontWeight: "600" }}>{day.day}:</span>
							<span>{day.hours}</span>
						</li>
					))}
				</ul>
			</div>

			
			<a
				href={centreData.map_link}
				target="_blank"
				rel="noopener noreferrer"
				style={{
					display: "inline-block",
					padding: "10px 20px",
					backgroundColor: "#3498db",
					color: "white",
					textDecoration: "none",
					borderRadius: "4px",
					fontWeight: "500",
				}}
			>
				Get Directions
			</a>
		</div>
	);
}
