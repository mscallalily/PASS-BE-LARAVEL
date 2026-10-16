# Parking fit proposal — 17 October 2026

This supersedes the earlier cumulative-ruler dimension interpretation. It is a metric prototype proposal, not a surveyed campus plan or an approved physical capacity.

## Measurement inputs and assumptions

Owner-reported motorcycle parcel: **30.96 × 8.80 m**, excluding the **3.06 m** shared access road. The existing gate location and diagonal access route are retained; internal aisles are rearranged. A 3.06 m total road width is shown as shared controlled access, not two simultaneous car lanes.

Car perimeter lengths, in supplied order: **18.42, 12.04, 2.60, 20.18, 33.37, 3.00, 3.36, 49.26 m**. The proposed mapping begins at the right edge and continues around the notch, returning along the long top edge. Bearings are estimated from the image with a closed polygon constrained to those lengths. The numbers alone do not determine the actual shape or area. Campus confirmation is required.

All bay and boundary geometry uses **8 drawing units per metre**. `ParkingMapLayout::SCALE` is separately the integer database precision factor, not a metre conversion.

## Motorcycle arrangement

84 compact bays, 3 rows × 28, each **1.00 × 2.00 m**.

- Length: 0.40 m end margin + 28.00 m bays + 2.56 m connecting end aisle = 30.96 m.
- Width: 0.20 + 2.00 + 1.20 + 2.00 + 1.20 + 2.00 + 0.20 = 8.80 m.
- Two 1.20 m aisles connect to the same 2.56 m end aisle and access road.
- This is a compact motorcycle/scooter walk/push proposal. Geometric rectangle fit does not prove rider access, turning movements, fire access or suitability for larger motorcycles. Validate with actual vehicles before physical marking. A larger bay requirement reduces capacity.

The 1 × 2 m bay reference is North Tyneside Council, *Transport and Highways SPD 2022*, Appendix E. It is not asserted as a Philippine minimum. The earlier 1.30 × 2.80 m template cannot fit 84 bays in this parcel even before aisles: bay rectangles alone need 305.76 m², versus 272.448 m² available.

## Car arrangement and unresolved capacity

The proposal draws **30 car bays**: 29 at **2.50 × 5.00 m**, plus the existing C-42 accessible designation at **4.00 × 5.00 m**. A 6.00 m reversing aisle and a 1.20 m front walkway at C-42 are drawn. The concave notch remains clear.

This is one conservative arrangement, **not a proven maximum**. Fitting 55 cars has not been demonstrated. The accessible count, continuous pedestrian route, entrances, swept paths and actual boundaries still need campus review. A single retained accessible designation does not establish compliance with the 2024 BP 344 IRR requirements.

All **139 database inventory records** remain: 84 motorcycle, 55 car. The 25 car records without proposed positions are retained with null coordinates. Historical transactions, slot status, IDs and gate state remain intact. Current assignment/capacity rules are not changed by this drawing update; this proposal must not be used as approval to admit 55 cars physically.

## Apply locally

After importing the matching backend and frontend bundles:

```powershell
php artisan route:clear
php artisan parking:refresh-layout
php artisan test --filter="ParkingMeasurementLayoutTest|AvailabilityMapTest"
```

Use `parking:refresh-layout` for this update rather than reseeding operating data. No schema migration is needed. In the frontend, run `npm run build`, then open `/app/parking/layout` or `/guest-dashboard/parking` and compare Plan/3D, area focus and Measurements & design basis.

## Verification scope

Geometry tests check reported lengths, bay dimensions, all drawn bays inside their own parcel, pairwise separation, and clearance from the access road/aisles/walkway. Coordinate refresh tests preserve active transactions, status, inventory and gate state. Server-side privacy tests keep user/guest maps read-only and exclude other people's identities. These software checks do **not** certify physical manoeuvring or approved capacity.

Sources are linked in the application's measurement panel: Philippine NBCP IRR (Pasig government reproduction), North Tyneside transport SPD and NCDA revised 2024 BP 344 IRR.

Validated locally: full backend suite 121 passed (18,743 assertions); targeted layout/privacy 5 passed (17,767 assertions); TypeScript and production build passed. Real-API WebGL/browser checks: 269 across four roles and 320/390/768/1440 px, followed by 42 focused checks after label placement refinement, both with zero page errors.
