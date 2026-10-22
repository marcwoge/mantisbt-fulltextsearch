# Testing Checklist — FullTextSearch

Manual test cases for verifying the plugin in a running MantisBT 2.25+ instance.
Each case lists the steps and the expected result. The plugin in this repository
was validated end-to-end against MantisBT 2.28.3 (test image now on 2.28.4) (see the bundled Docker setup in
`../docker/`).

> Suggested fixtures: one project with at least one custom field of type *String*
> linked to it, three issues — (A) summary "Login timeout error", description
> containing "login error"; (B) steps to reproduce containing "timeout"; (C) a
> custom field value "Firefox" plus one **public** bugnote containing "widget" and
> one **private** bugnote containing "secretword".

| #  | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| 1  | **Empty query** | Open *Full-Text Search*, leave the field empty, click *Search*. | A warning "Please enter at least one search term." is shown; no results table. |
| 2  | **Single word** | Search `timeout`. | All issues that contain "timeout" in any searched field are listed (A, B and C via its bugnote); count line shows the total. |
| 3  | **Multi-word AND** | Search `login timeout`. | Only issue A is returned (it is the only issue containing *both* words across its fields). |
| 4  | **Quoted phrase** | Search `"login error"`. | Issue A matches (adjacent phrase in the description); searching `"error login"` returns nothing. |
| 5  | **Project filter** | Select a specific project, search `timeout`. | Only matching issues from that project are listed; switching to *All Projects* widens the set. |
| 6  | **Status filter** | Pick one status in the multi-select, search `timeout`. | Only issues in the chosen status appear; picking a status none of the matches has yields no results. |
| 7  | **Custom field match** | Search `Firefox` with *Custom Fields* checked. | Issue C is found via its custom field value; un-checking *Custom Fields* and repeating returns nothing. |
| 8  | **Bugnote match** | Search `widget` with *Bugnotes* checked. | Issue C is found via its public bugnote; the match context shows a *Bugnote* snippet. |
| 9  | **Access control (non-admin)** | Log in as a Reporter-level user who cannot view private notes. Search `secretword`. | No results — the private bugnote must not leak the issue as a phantom hit. Searching `widget` (public note) still finds issue C. |
| 10 | **Pagination** | Ensure more than 25 matches exist, set *Results per page* to 25, search. | Page 1 shows 25 rows and a "1 / 2" indicator; the *next* link loads page 2 with the remaining rows and a working *previous* link. The query string keeps all filters. |
| 11 | **Highlighting & context** | Run any matching search. | Matched terms in the summary are wrapped in highlighted `<mark>` tags; expanding *Matches* shows one ≤120-char snippet per matched field, also highlighted. |
| 12 | **Bookmarkable URL** | Copy the results URL and open it in a new tab. | The same form state and results are reproduced (the form submits via GET). |
