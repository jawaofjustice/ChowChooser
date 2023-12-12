# ChowChooser SRS 2.2

## Spec

From the SRS template:

> 2.2 Product Functions
>
> Summarize the major functions the product must perform or must let the user perform. Details
> will be provided in Section 3, so only a high level summary (such as a bullet list) is needed here. Organize the
> functions to make them understandable to any reader of the SRS. A picture of the major groups of related
> requirements and how they relate, such as a top level data flow diagram or object class diagram, is often
> effective.

## 2.2 Product Functions

ChowChooser hosts user-generated lobbies in which lobby participants can vote for a restaurant to order food from, place orders from the winning restaurant, and view a summary of their order(s).

### 2.2.1 Create a lobby

Users can create a lobby with a custom name, a list of restaurants to vote on, a voting phase end time, and an ordering phase end time.

The only optional field is the voting phase end time; creating a lobby without one skips directly to the ordering phase.

### 2.2.2 Invite users to a lobby

Users can be invited to a lobby with a randomized code generated for each lobby.

Users input lobby invite codes in the main menu in order to join a lobby.

### 2.2.3 View a lobby

Users can view lobbies that they are members of.

The "View Lobby" page displays phase-dependent lobby information:

+ Voting phase: displays a list of restaurants to vote on.
+ Ordering phase: displays a user's placed orders.
  + The lobby admin is shown orders from all users.
+ Completed phase: summarizes the user's final order(s).
  + The lobby admin is shown orders from all users.

#### 2.2.3.1 Vote for restaurants

During a lobby's voting phase, users can cast a vote on one restaurant from the list.

A user can recast their vote during this phase.

#### 2.2.3.2 Place and edit orders

During a lobby's ordering phase, users can place orders for whichever restaurant won the voting phase.

Orders can also be edited and removed during this phase.
