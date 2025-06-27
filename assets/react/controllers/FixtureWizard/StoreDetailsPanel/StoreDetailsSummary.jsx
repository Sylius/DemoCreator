import React from "react";

const StoreDetailsSummary = ({ storeDetails }) => {
    if (!storeDetails) return null;
    return (
        <div style={{ background: "#f5f5f5", borderRadius: 8, padding: 16, marginBottom: 16, minWidth: 320 }}>
            <table style={{ width: "100%", fontSize: 15 }}>
                <tbody>
                    <tr><td><strong>Branża:</strong></td><td>{storeDetails.industry || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Języki:</strong></td><td>{storeDetails.locales?.join(", ") || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Waluty:</strong></td><td>{storeDetails.currencies?.join(", ") || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Kraje:</strong></td><td>{storeDetails.countries?.join(", ") || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Kategorie:</strong></td><td>{storeDetails.categories?.length ? storeDetails.categories.map(cat => cat.name).join(", ") : <em>Brak</em>}</td></tr>
                    <tr><td><strong>Liczba produktów/kategoria:</strong></td><td>{storeDetails.productsPerCat || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Styl opisu:</strong></td><td>{storeDetails.descriptionStyle || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Styl zdjęć:</strong></td><td>{storeDetails.imageStyle || <em>Brak</em>}</td></tr>
                    <tr><td><strong>Strefy:</strong></td><td>{storeDetails.zones && Object.keys(storeDetails.zones).length ? Object.keys(storeDetails.zones).join(", ") : <em>Brak</em>}</td></tr>
                </tbody>
            </table>
        </div>
    );
};

export default StoreDetailsSummary; 