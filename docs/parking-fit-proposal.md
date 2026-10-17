# Perimeter parking layout and circulation — 17 October 2026

Parking layout is now the default in Plan, 3D and SVG fallback. It uses the supplied car polygon more fully: 13 north bays (12 regular + existing C-42 accessible), 4 south-east bays, 3 south diagonal bays and 10 west diagonal bays. The count remains 30. A perimeter packing search found 31 candidate rectangles; one near the west entrance is omitted to keep access clear. This is not a proof of the global maximum capacity.

Regular car bays remain **2.50 × 5.00 m**. C-42 remains **4.00 × 5.00 m**, with a **1.20 m front walkway**. There is no shrinking of physical bay templates. The lower and western space now carries actual bay rectangles instead of being left as a large blank triangle. The central open space is reserved for reversing and circulation.

Each bank has a **6.00 m bay access aisle**. The western approach follows the separate **3.06 m shared road** through the proposed west opening. Entry and return arrows indicate circulation direction; they are not a vehicle tracking analysis or a certified U-turn. The junction, corner manoeuvres and accessible route still require physical/swept-path validation.

The outer car polygon has NOT been enlarged beyond the owner-provided dimensions. Edge lengths remain **18.42, 12.04, 2.60, 20.18, 33.37, 3.00, 3.36, 49.26 m**; their order/bearings remain assumed from the image, not a verified site survey. The motorcycle parcel and shared gate/road are retained: **30.96 × 8.80 m** parking and separate **3.06 m** road width.

## Owner-measured vehicle reference

| Vehicle | Width | Length | Measurement limitation |
| --- | --- | --- | --- |
| Car | 2.0447 m (6 ft 8.5 in) | 4.7752 m (15 ft 8 in) | Width is body-only; mirrors are not included. |
| Motorcycle | 0.5969 m (1 ft 11.5 in) | 1.9050 m (6 ft 3 in) | Whether mirrors/handlebars are included is unconfirmed. |

These measurements do not replace bay dimensions. Mirrors, wheelbase, steering lock, slopes and vehicle swept paths are unmeasured. The 1 × 2 m compact motorcycle template has only 0.095 m total extra length relative to the longest measured motorcycle; this remains a compact prototype proposal requiring physical checking.

## Motorcycle arrangement

84 compact bays, 3 rows × 28, each **1.00 × 2.00 m**.

- Length: 0.40 m end margin + 28.00 m bays + 2.56 m connecting end aisle = 30.96 m.
- Width: 0.20 + 2.00 + 1.20 + 2.00 + 1.20 + 2.00 + 0.20 = 8.80 m.
- Two 1.20 m aisles connect to the same 2.56 m end aisle and access road.
- This is a compact motorcycle/scooter walk/push proposal. Geometric rectangle fit does not prove rider access, turning movements, fire access or suitability for larger motorcycles. Validate with actual vehicles before physical marking. A larger bay requirement reduces capacity.

The 1 × 2 m bay reference is North Tyneside Council, *Transport and Highways SPD 2022*, Appendix E. It is not asserted as a Philippine minimum. The earlier 1.30 × 2.80 m template cannot fit 84 bays in this parcel even before aisles: bay rectangles alone need 305.76 m², versus 272.448 m² available.

## Inventory and safe update

All 139 inventory records are retained (55 car + 84 motorcycle). The optional Inventory diagram still shows all 55 schematic car symbols for record lookup; Parking layout shows 30 full-size proposed car bays. The remaining 25 car codes have no position in the physical proposal. Changing views does not approve or change operating capacity, access rules, slot status or existing transactions. Capacity changes require a separate settled campus decision.

After importing the matching bundles, run `php artisan route:clear` and `php artisan parking:refresh-layout`. No schema migration or operational seeding is needed. Build the frontend with `npm run build`. Check `/app/parking/layout` and `/guest-dashboard/parking`; Parking layout should be selected by default.

## Verification scope

Geometry checks retain all owner edge lengths and bay dimensions; sample all bay edges inside their parcels; reject overlaps with other bays, aisles, approach corridors and the shared connector; verify the 13/4/3/10 bank arrangement; and check painted flow lines do not cross car bays. Coordinate refresh preserves active sessions, IDs, status, inventory and gate state. Privacy tests cover both live slot records and the static alternative geometry. Software checks do not certify physical turning clearance or full accessibility compliance.

Sources remain linked in Measurements & design basis: Philippine NBCP IRR (Pasig government copy), North Tyneside transport guidance (2.5 × 5 m bays / 6 m reversing reference), and NCDA revised 2024 BP 344 IRR. The source bay/walkway dimensions do not establish accessible bay count or a complete continuous accessible route.
