# Complete slot map and measured proposal — 17 October 2026

The default Slot map now shows every C-01–C-55 and M-01–M-84 record. Car symbols are schematic, not physical bay dimensions. Measured proposal is a separate view retaining the earlier metre-sized 30-car arrangement. Both Plan and 3D use the selected view and the same live slot IDs/status.

This supersedes the earlier cumulative-ruler dimension interpretation. It is a metric prototype proposal, not a surveyed campus plan or an approved physical capacity.

## Measurement inputs and assumptions

Owner-reported motorcycle parcel: **30.96 × 8.80 m**, excluding the **3.06 m** shared access road. The existing gate location and diagonal access route are retained; internal aisles are rearranged. A 3.06 m total road width is shown as shared controlled access, not two simultaneous car lanes.

Car perimeter lengths, in supplied order: **18.42, 12.04, 2.60, 20.18, 33.37, 3.00, 3.36, 49.26 m**. The proposed mapping begins at the right edge and continues around the notch, returning along the long top edge. Bearings are estimated from the image with a closed polygon constrained to those lengths. The numbers alone do not determine the actual shape or area. Campus confirmation is required.

In Measured proposal, bay and boundary geometry uses **8 drawing units per metre**. In the complete Slot map, car symbols and aisles are dimensionless diagram shapes. `ParkingMapLayout::SCALE` is separately the integer database precision factor, not a metre conversion.

## Motorcycle arrangement

84 compact bays, 3 rows × 28, each **1.00 × 2.00 m**.

- Length: 0.40 m end margin + 28.00 m bays + 2.56 m connecting end aisle = 30.96 m.
- Width: 0.20 + 2.00 + 1.20 + 2.00 + 1.20 + 2.00 + 0.20 = 8.80 m.
- Two 1.20 m aisles connect to the same 2.56 m end aisle and access road.
- This is a compact motorcycle/scooter walk/push proposal. Geometric rectangle fit does not prove rider access, turning movements, fire access or suitability for larger motorcycles. Validate with actual vehicles before physical marking. A larger bay requirement reduces capacity.

The 1 × 2 m bay reference is North Tyneside Council, *Transport and Highways SPD 2022*, Appendix E. It is not asserted as a Philippine minimum. The earlier 1.30 × 2.80 m template cannot fit 84 bays in this parcel even before aisles: bay rectangles alone need 305.76 m², versus 272.448 m² available.

## Measured car arrangement and unresolved physical capacity

The separate Measured proposal draws **30 car bays**: 29 at **2.50 × 5.00 m**, plus the existing C-42 accessible designation at **4.00 × 5.00 m**. A 6.00 m reversing aisle and a 1.20 m front walkway at C-42 are drawn. The concave notch remains clear.

This is one conservative arrangement, **not a proven maximum**. Fitting 55 cars has not been demonstrated. The accessible count, continuous pedestrian route, entrances, swept paths and actual boundaries still need campus review. A single retained accessible designation does not establish compliance with the 2024 BP 344 IRR requirements.

All **139 database inventory records** remain: 84 motorcycle, 55 car. The default Slot map now gives all 55 car records diagram coordinates. In Measured proposal only, 25 car codes have no physical proposal position and are omitted from the drawing; their live data and inventory records remain intact. Switching views never creates or deletes inventory or parking sessions. Historical transactions, slot status, IDs and gate state remain intact. Current assignment/capacity rules are not changed by this drawing update; this proposal must not be used as approval to admit 55 cars physically.

## Complete slot diagram

The default car diagram uses three rows with 19, 19 and 17 individual symbols, for 55 total. C-42 retains its existing accessible designation. Symbol rectangles are kept inside the parcel and separate from each other and the displayed diagram aisles. Their smaller diagram dimensions must not be interpreted as approved car bay dimensions. The Measured proposal view preserves the nominal 2.50 × 5.00 m car template, the 6.00 m aisle and all original boundary inputs.

## Apply locally

After importing the matching backend and frontend bundles:

```powershell
php artisan route:clear
php artisan parking:refresh-layout
php artisan test --filter="ParkingMeasurementLayoutTest|AvailabilityMapTest"
```

Use `parking:refresh-layout` for this update rather than reseeding operating data. No schema migration is needed. In the frontend, run `npm run build`, then open `/app/parking/layout` or `/guest-dashboard/parking`. Slot map · 55 cars is selected by default. Compare Plan/3D, select C-55, then switch to Measured proposal to inspect the separate physical arrangement.

## Verification scope

Geometry tests check reported lengths, bay dimensions, all drawn bays inside their own parcel, pairwise separation, and clearance from the access road/aisles/walkway. Coordinate refresh tests preserve active transactions, status, inventory and gate state. Server-side privacy tests keep user/guest maps read-only and exclude other people's identities. These software checks do **not** certify physical manoeuvring or approved capacity.

Sources are linked in the application's measurement panel: Philippine NBCP IRR (Pasig government reproduction), North Tyneside transport SPD and NCDA revised 2024 BP 344 IRR.

Regression coverage includes all 55 car codes appearing exactly once, symbols inside the parcel without overlapping each other or the diagram aisles, C-55 entry/refresh/exit visibility, identical live IDs/status between layout views, and server-side user/guest redaction. The retained measured view has separate metre-sized geometry checks.
