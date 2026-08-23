# Privacy Policy

**Last updated: 22 August 2026**

## 1. Who is responsible

TáborPlanner (the "Service"), available at `tabory.barnas.net`, is a free, non-commercial
hobby project operated by:

**Michal Barnas** — a private individual, Slovak Republic
Contact: **michal@barnas.net**

There is no company behind the Service and no revenue is made from it.

This matters for how responsibility is divided:

- For **your account data** (Section 3.1) and the **technical data** the server records
  (Section 3.3), Michal Barnas is the **controller** under Article 4(7) GDPR.
- For **everything you type into the app about other people** — leader names, group
  assignments, invitation e-mail addresses, birthdays, feedback notes (Section 3.2) — **you
  and the organisation running your camp are the controller.** The Service only stores and
  displays that content on your instructions, and acts as a **processor**. You decide what
  is entered, why, and for how long it stays. See Section 4.

## 2. Scope

This policy covers the web application at `tabory.barnas.net` only. It does not cover any
other site linked from it, including `barnas.net`.

## 3. What data is processed

### 3.1 Account data

Collected when you register and when you use security features:

| Data                                             | Why                                                   |
| ------------------------------------------------ | ----------------------------------------------------- |
| Name                                             | Shown to other members of your camps                  |
| E-mail address                                   | Login, password reset, camp invitations, verification |
| Password (hashed, bcrypt)                        | Authentication — the plaintext is never stored        |
| E-mail verification timestamp                    | To know whether the address is confirmed              |
| Two-factor secret and recovery codes (encrypted) | Optional two-factor authentication                    |
| Passkey credentials (public key, credential ID)  | Optional passwordless login                           |
| "Remember me" token                              | Keeps you signed in if you ask for it                 |
| Account creation and update timestamps           | Housekeeping                                          |

Optionally, if you choose to use the AI summary feature:

| Data                                           | Why                                                             |
| ---------------------------------------------- | --------------------------------------------------------------- |
| Your Google Gemini API key (encrypted at rest) | So summaries can be generated with _your_ key — see Section 6.3 |

### 3.2 Content you enter

The Service stores what you and your fellow organisers put into it:

- Camps: name, year, dates, description, location, icon and colour
- Camp days: date, name days, **birthdays**, materials, notes and reviews
- Time blocks and program entries: titles, descriptions, times, notes, materials,
  the person responsible, completion status and points
- Activities, activity libraries and categories, including shared ones
- **Leader names** (leaders do not need an account to be listed), their colours
  and their group memberships
- Camp groups, group types and their scores
- Invitations: the **e-mail address** you invite, and the invitation token
- Daily reviews: star ratings, free-text reasons, answers to your camp's own
  feedback questions, and who wrote them
- Saved plan and library versions (snapshots of the above)
- AI summaries generated from the above, if you use that feature

Some of these fields are free text. **Whatever you type into them is stored as-is** — see
Section 11 about entering data on children.

### 3.3 Technical data

Recorded automatically, as with any web server:

- **Server logs**: IP address, date and time, requested URL, HTTP status, user agent
- **Session records** (stored in the database): a session identifier, your IP address,
  your user agent and the time of last activity
- Error logs, which may include the above when something goes wrong

## 4. Controller and processor, in plain terms

If you run a camp and enter the names of your leaders, their feedback and the e-mail
addresses you invite, **you are the one deciding to process that data.** Under GDPR that
makes you (or your parish, association or organisation) the controller for it. The Service
gives you the tools; it does not decide what you collect.

Practically, that means:

- You are responsible for having a legal basis for the people whose data you enter —
  usually their consent, or a legitimate interest in organising the camp.
- You should tell those people that their data is in TáborPlanner and who operates it.
- You must not enter data you have no right to enter (Section 11).
- The operator will act on your instructions: he will not use your camp content for any
  purpose of his own, will not sell it, and will not share it with anyone except the
  processors in Section 6.

## 5. Legal bases and purposes

| Purpose                                                                       | Legal basis (Art. 6 GDPR)                                                      |
| ----------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Creating and running your account, letting you sign in                        | 6(1)(b) — performance of a contract with you                                   |
| Storing and displaying your camp content                                      | 6(1)(b), and 6(1)(f) where the data concerns other camp members                |
| Sending transactional e-mail (verification, password reset, camp invitations) | 6(1)(b)                                                                        |
| Keeping the Service secure: logs, session records, rate limiting, two-factor  | 6(1)(f) — legitimate interest in preventing abuse                              |
| Remembering your language, theme and sidebar preference                       | 6(1)(f) — legitimate interest in a working interface, and your explicit choice |
| Sending your text to Google Gemini for a summary                              | 6(1)(a) — your consent, given by supplying a key and pressing the button       |

No personal data is used for advertising, profiling or resale. There is nothing to sell.

## 6. Who else sees the data

### 6.1 Hosting

The Service runs on a single virtual server rented from **Hetzner Online GmbH**
(Industriestr. 25, 91710 Gunzenhausen, Germany), in a data centre inside the European
Union. The application, the database and the backups all live on that infrastructure.
Hetzner acts as a processor.

### 6.2 E-mail delivery

Outgoing e-mail (verification, password reset, camp invitations) is handed to the SMTP
service configured for the Service, which processes the recipient address and the message
content in order to deliver it.

### 6.3 Google Gemini — only if you switch it on

The AI summary feature is **off by default and requires you to paste your own Google
Gemini API key.** If you supply a key and ask for a summary, the following is sent to
Google's Gemini API (`generativelanguage.googleapis.com`):

- the program of the camp or day being summarised — activity titles, descriptions and times
- the feedback your leaders wrote, including their free-text answers and reasons

This is a transfer to **Google LLC in the United States** and is governed by your own
agreement with Google and by Google's API terms — not by this policy. Because you supply
the key, you are Google's customer for that request.

**If you do not want your camp's text leaving the EU, do not enter a Gemini API key.**
Every other feature works without it. You can delete a stored key at any time in
Settings → AI, which removes it from the database immediately.

### 6.4 Nobody else

There are no analytics, no advertising networks, no tracking pixels, no chat widgets and no
content delivery networks. Web fonts are compiled into the Service's own assets at build
time and served from its own domain, so your browser does not contact any third-party font
host.

Data may additionally be disclosed where required by law.

## 7. Transfers outside the EU

None, except the Gemini case in Section 6.3, which happens only when you deliberately
enable it with your own key.

## 8. How long data is kept

| Data                  | Retention                                                                           |
| --------------------- | ----------------------------------------------------------------------------------- |
| Account data          | Until you delete your account                                                       |
| Camp content          | Until you or another camp member deletes it, or until the owning account is deleted |
| Session records       | Until they expire (2 hours of inactivity) or you log out                            |
| Server and error logs | Rotated by the server; typically a few weeks                                        |
| Backups               | Overwritten on the backup cycle; deletions propagate as backups rotate              |

Deleting your account removes your user record, and everything that hangs off it, from the
live database immediately and permanently. **Please note: camps you own are deleted with
your account, including for the other leaders who collaborate on them.** If a camp should
outlive your account, transfer it or ask another member to recreate it before you delete.

## 9. Your rights

Under the GDPR you may:

- **Access** your data (Art. 15) — the Service has a one-click export: Settings → Profile →
  _Download my data_, which produces a JSON file with your account and your camp content
- **Rectify** inaccurate data (Art. 16) — most of it is directly editable in the app
- **Erase** your data (Art. 17) — Settings → Profile → _Delete account_
- **Restrict** or **object to** processing (Art. 18 and 21)
- **Port** your data to another service (Art. 20) — the JSON export above is machine-readable
- **Withdraw consent** at any time, without affecting processing already carried out —
  for the AI feature, by removing your Gemini key

To exercise a right that the app does not already handle, write to
**michal@barnas.net**. Expect a reply within 30 days. Because the Service is run by
one person in his spare time, please be patient, and write again if you hear nothing.

If a camp organiser entered your data and you want it removed, ask that organiser first —
they control it. If they do not respond, write to the address above and it will be dealt
with.

## 10. Security

- All traffic is served over HTTPS.
- Passwords are stored as bcrypt hashes; the plaintext is never written down.
- Gemini API keys and two-factor secrets are encrypted at rest with the application key.
- Optional two-factor authentication and passkeys are available in Settings → Security.
- Access to camps is checked on every request against your membership.
- The server is kept patched and the database is not reachable from the public internet.

No system is perfectly secure. If you find a vulnerability, please report it to
**michal@barnas.net** rather than disclosing it publicly.

## 11. Children's data

The Service is built for the **adults who organise a camp** — leaders, animators and
coordinators. Accounts are for them.

It is not designed to hold records about the **children attending** the camp, and you
should not use it that way. In particular, please do not type children's names, birth
dates, health information, contact details or photographs into free-text fields such as
day notes, birthdays or group names.

If you do enter data about a minor anyway, **you are the controller of it.** You are the
one who needs a legal basis — in practice, the consent of a parent or guardian — and you
are responsible for the consequences. The operator has no relationship with those children
and no way of knowing their data is there.

## 12. Automated decision-making

There is none. The AI summary feature produces text for humans to read; it makes no
decisions about anybody and has no legal or similarly significant effect.

## 13. Changes to this policy

This policy may be updated as the Service changes. The date at the top always reflects the
current version. Significant changes will be announced in the app.

## 14. Complaints

If you believe your data is being handled unlawfully, you may lodge a complaint with the
Slovak supervisory authority:

**Úrad na ochranu osobných údajov Slovenskej republiky**
Hraničná 12, 820 07 Bratislava 27, Slovak Republic
`dataprotection.gov.sk`

You may also complain to the authority in your own EU country of residence.
